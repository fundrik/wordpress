<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\DonationRepository;

use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\Exceptions\DonationFactoryException;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationAlreadyExistsException;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationNotFoundException;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationRepository;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationRepositoryException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseDuplicateKeyException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\Fixtures\FrozenClock;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationRepository::class )]
#[CoversClass( DonationAlreadyExistsException::class )]
#[CoversClass( DonationNotFoundException::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( DonationId::class )]
final class DonationRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_donations';
	private const string DONATION_ID = '123e4567-e89b-42d3-a456-426614174000';
	private const string INVALID_CAMPAIGN_ID = '223e4567-e89b-42d3-a456-426614174000';
	private const string PAYMENT_ID = 'payment-123';
	private const string CURRENT_TIME = '2026-11-01 10:00:00.123456';

	private DatabasePort&MockInterface $database;
	private DonationRepository $repository;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->repository = new DonationRepository(
			$this->database,
			new DonationFactory(),
			new FrozenClock( UtcDateTime::create_from_format( self::CURRENT_TIME, 'Y-m-d H:i:s.u' ) ),
		);
	}

	#[Test]
	public function find_by_id_returns_mapped_donation(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn(
				$this->make_donation_row(
					id: self::DONATION_ID,
					version: 3,
					campaign_id: 77,
					amount: 1_250,
					currency_code: 'USD',
					status: 'succeeded',
					payment_id: self::PAYMENT_ID,
				),
			);

		$donation = $this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );

		self::assertNotNull( $donation );
		self::assertSame( self::DONATION_ID, $donation->get_id()->get_value() );
		self::assertSame( 3, $donation->get_version()->get_value() );
		self::assertSame( 77, $donation->get_campaign_id()->get_value() );
		self::assertSame( 1_250, $donation->get_money()->get_amount()->get_value() );
		self::assertSame( 'USD', $donation->get_money()->get_currency()->get_code() );
		self::assertSame( 'succeeded', $donation->get_status()->value );
		self::assertSame( self::PAYMENT_ID, $donation->get_payment_id()?->get_value() );
	}

	#[Test]
	public function find_by_id_maps_nullable_payment_id(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn(
				$this->make_donation_row(
					id: self::DONATION_ID,
					status: 'created',
					payment_id: null,
				),
			);

		$donation = $this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );

		self::assertNotNull( $donation );
		self::assertSame( 'created', $donation->get_status()->value );
		self::assertNull( $donation->get_payment_id() );
	}

	#[Test]
	public function find_by_id_returns_null_when_donation_is_not_found(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( null );

		self::assertNull( $this->repository->find_by_id( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function find_by_id_throws_for_invalid_donation_id(): void {

		try {
			$this->repository->find_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected the donation lookup to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( 'Donation ID must be a valid UUIDv4. Given: 7.', $exception->getMessage() );
			self::assertInstanceOf( InvalidDonationIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_by_id_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andThrow( $database_exception );

		try {
			$this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );
			self::fail( 'Expected the donation lookup to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( sprintf( 'Failed to fetch donation "%s".', self::DONATION_ID ), $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'required_row_field_cases' )]
	public function find_by_id_throws_when_required_row_field_is_missing(
		string $field,
		string $row_id_in_message,
	): void {

		$row = $this->make_donation_row( id: self::DONATION_ID );
		unset( $row[ $field ] );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( $row );

		try {
			$this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );
			self::fail( 'Expected donation row mapping to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map donation row "%s".', $row_id_in_message ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( ArrayExtractionException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'invalid_row_field_cases' )]
	public function find_by_id_throws_when_row_field_has_invalid_type(
		string $field,
		int|string|null $invalid_value,
		string $row_id_in_message,
	): void {

		$row = $this->make_donation_row( id: self::DONATION_ID );
		$row[ $field ] = $invalid_value;

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( $row );

		try {
			$this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );
			self::fail( 'Expected donation row mapping to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map donation row "%s".', $row_id_in_message ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( ArrayExtractionException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_by_id_throws_when_donation_status_is_invalid(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( $this->make_donation_row( id: self::DONATION_ID, status: 'invalid' ) );

		try {
			$this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );
			self::fail( 'Expected donation row mapping to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map donation row "%s".', self::DONATION_ID ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( DonationFactoryException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_by_payment_id_returns_mapped_donation(): void {

		$this->database
			->shouldReceive( 'get_by_column' )
			->once()
			->with( self::TABLE_NAME, 'payment_id', self::PAYMENT_ID )
			->andReturn(
				$this->make_donation_row(
					id: self::DONATION_ID,
					status: 'pending',
					payment_id: self::PAYMENT_ID,
				),
			);

		$donation = $this->repository->find_by_payment_id( PaymentId::create( self::PAYMENT_ID ) );

		self::assertNotNull( $donation );
		self::assertSame( self::DONATION_ID, $donation->get_id()->get_value() );
		self::assertSame( self::PAYMENT_ID, $donation->get_payment_id()?->get_value() );
	}

	#[Test]
	public function find_by_payment_id_returns_null_when_donation_is_not_found(): void {

		$this->database
			->shouldReceive( 'get_by_column' )
			->once()
			->with( self::TABLE_NAME, 'payment_id', self::PAYMENT_ID )
			->andReturn( null );

		self::assertNull( $this->repository->find_by_payment_id( PaymentId::create( self::PAYMENT_ID ) ) );
	}

	#[Test]
	public function find_by_payment_id_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'get_by_column' )
			->once()
			->with( self::TABLE_NAME, 'payment_id', self::PAYMENT_ID )
			->andThrow( $database_exception );

		try {
			$this->repository->find_by_payment_id( PaymentId::create( self::PAYMENT_ID ) );
			self::fail( 'Expected the donation lookup by payment ID to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Failed to fetch donation for payment "%s".', self::PAYMENT_ID ),
				$exception->getMessage(),
			);
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'existence_cases' )]
	public function exists_by_campaign_id_returns_database_result( bool $exists ): void {

		$this->database
			->shouldReceive( 'exists_by_column' )
			->once()
			->with( self::TABLE_NAME, 'campaign_id', 77 )
			->andReturn( $exists );

		self::assertSame( $exists, $this->repository->exists_by_campaign_id( EntityId::create( 77 ) ) );
	}

	#[Test]
	public function exists_by_campaign_id_throws_for_invalid_campaign_id(): void {

		try {
			$this->repository->exists_by_campaign_id( EntityId::create( self::INVALID_CAMPAIGN_ID ) );
			self::fail( 'Expected the donation existence check to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Campaign ID must be a positive integer. Given: %s.', self::INVALID_CAMPAIGN_ID ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function exists_by_campaign_id_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'exists_by_column' )
			->once()
			->with( self::TABLE_NAME, 'campaign_id', 77 )
			->andThrow( $database_exception );

		try {
			$this->repository->exists_by_campaign_id( EntityId::create( 77 ) );
			self::fail( 'Expected the donation existence check to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( 'Failed to check donations for campaign "77".', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'existence_cases' )]
	public function exists_by_id_returns_database_result( bool $exists ): void {

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( $exists );

		self::assertSame( $exists, $this->repository->exists_by_id( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function exists_by_id_throws_for_invalid_donation_id(): void {

		try {
			$this->repository->exists_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected the donation existence check to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( 'Donation ID must be a valid UUIDv4. Given: 7.', $exception->getMessage() );
			self::assertInstanceOf( InvalidDonationIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function exists_by_id_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andThrow( $database_exception );

		try {
			$this->repository->exists_by_id( EntityId::create( self::DONATION_ID ) );
			self::fail( 'Expected the donation existence check to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Failed to check donation "%s" existence.', self::DONATION_ID ),
				$exception->getMessage(),
			);
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function insert_persists_donation_and_returns_persisted_snapshot(): void {

		$donation = $this->make_created_donation(
			id: self::DONATION_ID,
			campaign_id: 77,
			amount: 1_250,
			currency: 'USD',
		);
		$this->database
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => self::DONATION_ID,
					'version' => 1,
					'campaign_id' => 77,
					'amount' => 1_250,
					'currency_code' => 'USD',
					'status' => 'created',
					'payment_id' => null,
					'created_at' => self::CURRENT_TIME,
					'updated_at' => null,
				],
			);

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn(
				$this->make_donation_row(
					id: self::DONATION_ID,
					campaign_id: 77,
					amount: 1_250,
					currency_code: 'USD',
					status: 'created',
					payment_id: null,
					updated_at: null,
				),
			);

		$persisted = $this->repository->insert( $donation );

		self::assertSame( self::DONATION_ID, $persisted->get_id()->get_value() );
		self::assertSame( 1, $persisted->get_version()->get_value() );
		self::assertSame( 'created', $persisted->get_status()->value );
		self::assertNull( $persisted->get_payment_id() );
	}

	#[Test]
	public function insert_throws_for_invalid_donation_id(): void {

		try {
			$this->repository->insert( $this->make_created_donation( id: 7, campaign_id: 77 ) );
			self::fail( 'Expected the donation insert to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( 'Donation ID must be a valid UUIDv4. Given: 7.', $exception->getMessage() );
			self::assertInstanceOf( InvalidDonationIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function insert_throws_for_invalid_campaign_id(): void {

		$donation = $this->make_created_donation(
			id: self::DONATION_ID,
			campaign_id: EntityId::create( self::INVALID_CAMPAIGN_ID ),
		);

		try {
			$this->repository->insert( $donation );
			self::fail( 'Expected the donation insert to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Campaign ID must be a positive integer. Given: %s.', self::INVALID_CAMPAIGN_ID ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function insert_throws_when_donation_version_is_not_initial(): void {

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf( 'Cannot insert donation "%s": version must be initial. Given: 2.', self::DONATION_ID ),
		);

		$this->repository->insert(
			$this->make_pending_donation( id: self::DONATION_ID, campaign_id: 77, version: 2 ),
		);
	}

	#[Test]
	public function insert_throws_specific_exception_for_duplicate_donation_id(): void {

		$database_exception = new FakeDatabaseDuplicateKeyException( 'Duplicate key.' );

		$this->database
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) )
			->andThrow( $database_exception );

		try {
			$this->repository->insert(
				$this->make_created_donation( id: self::DONATION_ID, campaign_id: 77 ),
			);
			self::fail( 'Expected the duplicate donation insert to throw.' );
		} catch ( DonationAlreadyExistsException $exception ) {
			self::assertSame(
				sprintf( 'Cannot insert donation "%s": donation already exists.', self::DONATION_ID ),
				$exception->getMessage(),
			);
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function insert_throws_when_database_insert_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) )
			->andThrow( $database_exception );

		try {
			$this->repository->insert(
				$this->make_created_donation( id: self::DONATION_ID, campaign_id: 77 ),
			);
			self::fail( 'Expected the donation insert to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( sprintf( 'Failed to insert donation "%s".', self::DONATION_ID ), $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function insert_throws_when_persisted_snapshot_cannot_be_found(): void {

		$this->database
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( null );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Donation "%s" was inserted, but fetching persisted snapshot failed.',
				self::DONATION_ID,
			),
		);

		$this->repository->insert(
			$this->make_created_donation( id: self::DONATION_ID, campaign_id: 77 ),
		);
	}

	#[Test]
	public function update_persists_next_version_and_returns_persisted_snapshot(): void {

		$donation = $this->make_pending_donation(
			id: self::DONATION_ID,
			campaign_id: 77,
			amount: 1_250,
			currency: 'USD',
			payment_id: self::PAYMENT_ID,
			version: 3,
		);
		$this->database
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'status' => 'pending',
					'payment_id' => self::PAYMENT_ID,
					'updated_at' => self::CURRENT_TIME,
					'version' => 4,
				],
				[
					'id' => self::DONATION_ID,
					'version' => 3,
				],
			)
			->andReturn( 1 );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn(
				$this->make_donation_row(
					id: self::DONATION_ID,
					version: 4,
					campaign_id: 77,
					amount: 1_250,
					currency_code: 'USD',
					status: 'pending',
					payment_id: self::PAYMENT_ID,
				),
			);

		$persisted = $this->repository->update( $donation );

		self::assertSame( 4, $persisted->get_version()->get_value() );
		self::assertSame( 'pending', $persisted->get_status()->value );
		self::assertSame( self::PAYMENT_ID, $persisted->get_payment_id()?->get_value() );
	}

	#[Test]
	public function update_throws_for_invalid_donation_id(): void {

		try {
			$this->repository->update(
				$this->make_pending_donation( id: 7, campaign_id: 77, version: 3 ),
			);
			self::fail( 'Expected the donation update to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( 'Donation ID must be a valid UUIDv4. Given: 7.', $exception->getMessage() );
			self::assertInstanceOf( InvalidDonationIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function update_throws_when_database_update_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				Mockery::type( 'array' ),
				[
					'id' => self::DONATION_ID,
					'version' => 3,
				],
			)
			->andThrow( $database_exception );

		try {
			$this->repository->update(
				$this->make_pending_donation( id: self::DONATION_ID, campaign_id: 77, version: 3 ),
			);
			self::fail( 'Expected the donation update to throw.' );
		} catch ( DonationRepositoryException $exception ) {
			self::assertSame( sprintf( 'Failed to update donation "%s".', self::DONATION_ID ), $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function update_throws_when_donation_is_not_found(): void {

		$this->database
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( false );

		$this->expectException( DonationNotFoundException::class );
		$this->expectExceptionMessage(
			sprintf( 'Cannot update donation "%s": persisted record not found.', self::DONATION_ID ),
		);

		$this->repository->update(
			$this->make_pending_donation( id: self::DONATION_ID, campaign_id: 77, version: 3 ),
		);
	}

	#[Test]
	public function update_throws_when_donation_version_does_not_match(): void {

		$this->database
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( true );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf( 'Cannot update donation "%s": version mismatch.', self::DONATION_ID ),
		);

		$this->repository->update(
			$this->make_pending_donation( id: self::DONATION_ID, campaign_id: 77, version: 3 ),
		);
	}

	#[Test]
	public function update_throws_when_persisted_snapshot_cannot_be_found(): void {

		$this->database
			->shouldReceive( 'update' )
			->once()
			->andReturn( 1 );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( null );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Donation "%s" was updated, but fetching persisted snapshot failed.',
				self::DONATION_ID,
			),
		);

		$this->repository->update(
			$this->make_pending_donation( id: self::DONATION_ID, campaign_id: 77, version: 3 ),
		);
	}

	public static function required_row_field_cases(): iterable {

		yield 'id' => [ 'id', '<unavailable>' ];
		yield 'version' => [ 'version', self::DONATION_ID ];
		yield 'campaign id' => [ 'campaign_id', self::DONATION_ID ];
		yield 'amount' => [ 'amount', self::DONATION_ID ];
		yield 'currency code' => [ 'currency_code', self::DONATION_ID ];
		yield 'status' => [ 'status', self::DONATION_ID ];
		yield 'payment id' => [ 'payment_id', self::DONATION_ID ];
	}

	public static function invalid_row_field_cases(): iterable {

		yield 'id' => [ 'id', null, '<unavailable>' ];
		yield 'version' => [ 'version', null, self::DONATION_ID ];
		yield 'campaign id' => [ 'campaign_id', null, self::DONATION_ID ];
		yield 'amount' => [ 'amount', null, self::DONATION_ID ];
		yield 'currency code' => [ 'currency_code', null, self::DONATION_ID ];
		yield 'status' => [ 'status', null, self::DONATION_ID ];
		yield 'payment id' => [ 'payment_id', 123, self::DONATION_ID ];
	}

	public static function existence_cases(): iterable {

		yield 'record exists' => [ true ];
		yield 'record does not exist' => [ false ];
	}
}
