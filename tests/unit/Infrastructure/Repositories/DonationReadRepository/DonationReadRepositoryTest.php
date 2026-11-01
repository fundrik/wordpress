<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\DonationReadRepository;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\DonationReadRepository\DonationReadException;
use Fundrik\WordPress\Infrastructure\Repositories\DonationReadRepository\DonationReadRepository;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( DonationReadRepository::class )]
#[UsesClass( DonationId::class )]
final class DonationReadRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_donations';
	private const string DONATION_ID = '123e4567-e89b-42d3-a456-426614174000';
	private const string SECOND_DONATION_ID = '223e4567-e89b-42d3-a456-426614174000';

	private DatabasePort&MockInterface $database;
	private DonationReadRepository $repository;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->repository = new DonationReadRepository( $this->database );
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
					campaign_id: 77,
					amount: 1_250,
					currency_code: 'USD',
					status: 'succeeded',
					payment_id: 'payment-123',
					created_at: '2026-01-01 10:00:00.123456',
					updated_at: '2026-01-01 11:00:00.654321',
				),
			);

		$donation = $this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );

		self::assertNotNull( $donation );
		self::assertSame( self::DONATION_ID, $donation->get_id() );
		self::assertSame( 77, $donation->get_campaign_id() );
		self::assertSame( 1_250, $donation->get_amount() );
		self::assertSame( 'USD', $donation->get_currency_code() );
		self::assertSame( 'succeeded', $donation->get_status() );
		self::assertSame( 'payment-123', $donation->get_payment_id() );
		self::assertSame( '2026-01-01 10:00:00.123456', $donation->get_created_at()->format( 'Y-m-d H:i:s.u' ) );
		self::assertSame( '2026-01-01 11:00:00.654321', $donation->get_updated_at()?->format( 'Y-m-d H:i:s.u' ) );
	}

	#[Test]
	public function find_by_id_maps_nullable_fields(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn(
				$this->make_donation_row(
					id: self::DONATION_ID,
					status: 'created',
					payment_id: null,
					updated_at: null,
				),
			);

		$donation = $this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );

		self::assertNotNull( $donation );
		self::assertSame( 'created', $donation->get_status() );
		self::assertNull( $donation->get_payment_id() );
		self::assertNull( $donation->get_updated_at() );
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
		} catch ( DonationReadException $exception ) {
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
		} catch ( DonationReadException $exception ) {
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
		} catch ( DonationReadException $exception ) {
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
		} catch ( DonationReadException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map donation row "%s".', $row_id_in_message ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( ArrayExtractionException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'invalid_datetime_cases' )]
	public function find_by_id_throws_when_row_has_invalid_datetime( string $field ): void {

		$row = $this->make_donation_row( id: self::DONATION_ID );
		$row[ $field ] = 'invalid-date';

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( $row );

		try {
			$this->repository->find_by_id( EntityId::create( self::DONATION_ID ) );
			self::fail( 'Expected donation row mapping to throw.' );
		} catch ( DonationReadException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map donation row "%s".', self::DONATION_ID ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidUtcDateTimeException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function paginate_returns_mapped_donations_and_pagination_metadata(): void {

		$this->database
			->shouldReceive( 'paginate' )
			->once()
			->with( self::TABLE_NAME, 2, 20 )
			->andReturn(
				[
					[
						$this->make_donation_row( id: self::DONATION_ID, amount: 1_000 ),
						$this->make_donation_row( id: self::SECOND_DONATION_ID, amount: 2_000 ),
					],
					21,
				],
			);

		$result = $this->repository->paginate( 2, 20 );

		self::assertSame( 2, $result->get_page() );
		self::assertSame( 20, $result->get_per_page() );
		self::assertSame( 21, $result->get_total() );
		self::assertCount( 2, $result->get_items() );
		self::assertSame( self::DONATION_ID, $result->get_items()[0]->get_id() );
		self::assertSame( 1_000, $result->get_items()[0]->get_amount() );
		self::assertSame( self::SECOND_DONATION_ID, $result->get_items()[1]->get_id() );
		self::assertSame( 2_000, $result->get_items()[1]->get_amount() );
	}

	#[Test]
	public function paginate_returns_empty_items_when_database_has_no_rows(): void {

		$this->database
			->shouldReceive( 'paginate' )
			->once()
			->with( self::TABLE_NAME, 1, 20 )
			->andReturn( [ [], 0 ] );

		$result = $this->repository->paginate( 1, 20 );

		self::assertSame( [], $result->get_items() );
		self::assertSame( 1, $result->get_page() );
		self::assertSame( 20, $result->get_per_page() );
		self::assertSame( 0, $result->get_total() );
	}

	#[Test]
	public function paginate_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'paginate' )
			->once()
			->with( self::TABLE_NAME, 1, 20 )
			->andThrow( $database_exception );

		try {
			$this->repository->paginate( 1, 20 );
			self::fail( 'Expected the paginated donation lookup to throw.' );
		} catch ( DonationReadException $exception ) {
			self::assertSame( 'Failed to retrieve paginated donations.', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	public static function required_row_field_cases(): iterable {

		yield 'id' => [ 'id', '<unavailable>' ];
		yield 'campaign id' => [ 'campaign_id', self::DONATION_ID ];
		yield 'amount' => [ 'amount', self::DONATION_ID ];
		yield 'currency code' => [ 'currency_code', self::DONATION_ID ];
		yield 'status' => [ 'status', self::DONATION_ID ];
		yield 'payment id' => [ 'payment_id', self::DONATION_ID ];
		yield 'created at' => [ 'created_at', self::DONATION_ID ];
		yield 'updated at' => [ 'updated_at', self::DONATION_ID ];
	}

	public static function invalid_row_field_cases(): iterable {

		yield 'id' => [ 'id', null, '<unavailable>' ];
		yield 'campaign id' => [ 'campaign_id', null, self::DONATION_ID ];
		yield 'amount' => [ 'amount', null, self::DONATION_ID ];
		yield 'currency code' => [ 'currency_code', null, self::DONATION_ID ];
		yield 'status' => [ 'status', null, self::DONATION_ID ];
		yield 'payment id' => [ 'payment_id', 123, self::DONATION_ID ];
		yield 'created at' => [ 'created_at', null, self::DONATION_ID ];
		yield 'updated at' => [ 'updated_at', 123, self::DONATION_ID ];
	}

	public static function invalid_datetime_cases(): iterable {

		yield 'created at' => [ 'created_at' ];
		yield 'updated at' => [ 'updated_at' ];
	}
}
