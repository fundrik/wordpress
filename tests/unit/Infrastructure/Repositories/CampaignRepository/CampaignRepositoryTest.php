<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignAlreadyExistsException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignNotFoundException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignRepository;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignRepositoryException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseDuplicateKeyException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseRowNotFoundException;
use Fundrik\WordPress\Tests\Fixtures\FrozenClock;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignRepository::class )]
#[CoversClass( CampaignAlreadyExistsException::class )]
#[CoversClass( CampaignNotFoundException::class )]
#[UsesClass( CampaignId::class )]
final class CampaignRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_campaigns';
	private const string CURRENT_TIME = '2026-11-01 10:00:00';

	private DatabasePort&MockInterface $database;
	private CampaignRepository $repository;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->repository = new CampaignRepository(
			$this->database,
			new CampaignFactory(),
			new FrozenClock( UtcDateTime::create_from_format( self::CURRENT_TIME, 'Y-m-d H:i:s' ) ),
		);
	}

	#[Test]
	public function find_by_id_returns_mapped_campaign(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				$this->make_campaign_row(
					id: 7,
					version: 3,
					title: 'Campaign title',
					accepts_donations: true,
					currency_code: 'USD',
					target_amount: 12_300,
				),
			);

		$campaign = $this->repository->find_by_id( EntityId::create( 7 ) );

		self::assertNotNull( $campaign );
		self::assertSame( 7, $campaign->get_id()->get_value() );
		self::assertSame( 3, $campaign->get_version()->get_value() );
		self::assertSame( 'Campaign title', $campaign->get_title() );
		self::assertTrue( $campaign->accepts_donations() );
		self::assertSame( 'USD', $campaign->get_target()->get_currency()->get_code() );
		self::assertSame( 12_300, $campaign->get_target()->get_amount()?->get_value() );
	}

	#[Test]
	public function find_by_id_maps_nullable_target_amount(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $this->make_campaign_row( id: 7, target_amount: null ) );

		$campaign = $this->repository->find_by_id( EntityId::create( 7 ) );

		self::assertNotNull( $campaign );
		self::assertFalse( $campaign->has_target() );
		self::assertNull( $campaign->get_target()->get_amount() );
	}

	#[Test]
	public function find_by_id_returns_null_when_campaign_is_not_found(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		self::assertNull( $this->repository->find_by_id( EntityId::create( 7 ) ) );
	}

	#[Test]
	public function find_by_id_throws_for_invalid_campaign_id(): void {

		try {
			$this->repository->find_by_id(
				EntityId::create( '123e4567-e89b-12d3-a456-426614174000' ),
			);
			self::fail( 'Expected the campaign lookup to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame(
				'Campaign ID must be a positive integer. Given: 123e4567-e89b-12d3-a456-426614174000.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_by_id_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( $database_exception );

		try {
			$this->repository->find_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected the campaign lookup to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame( 'Failed to fetch campaign "7".', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'required_row_field_cases' )]
	public function find_by_id_throws_when_required_row_field_is_missing(
		string $field,
		string $row_id_in_message,
	): void {

		$row = $this->make_campaign_row( id: 7 );
		unset( $row[ $field ] );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $row );

		try {
			$this->repository->find_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected campaign row mapping to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map campaign row "%s".', $row_id_in_message ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( ArrayExtractionException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'invalid_row_field_cases' )]
	public function find_by_id_throws_when_row_field_has_invalid_type(
		string $field,
		?string $invalid_value,
		string $row_id_in_message,
	): void {

		$row = $this->make_campaign_row( id: 7 );
		$row[ $field ] = $invalid_value;

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $row );

		try {
			$this->repository->find_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected campaign row mapping to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map campaign row "%s".', $row_id_in_message ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( ArrayExtractionException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_by_id_throws_when_campaign_title_is_whitespace(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $this->make_campaign_row( id: 7, title: ' ' ) );

		try {
			$this->repository->find_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected campaign row mapping to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame( 'Failed to map campaign row "7".', $exception->getMessage() );
			self::assertInstanceOf( CampaignFactoryException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'campaign_existence_cases' )]
	public function exists_by_id_returns_database_result( bool $exists ): void {

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $exists );

		self::assertSame( $exists, $this->repository->exists_by_id( EntityId::create( 7 ) ) );
	}

	#[Test]
	public function exists_by_id_throws_for_invalid_campaign_id(): void {

		try {
			$this->repository->exists_by_id(
				EntityId::create( '123e4567-e89b-12d3-a456-426614174000' ),
			);
			self::fail( 'Expected the campaign existence check to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame(
				'Campaign ID must be a positive integer. Given: 123e4567-e89b-12d3-a456-426614174000.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function exists_by_id_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( $database_exception );

		try {
			$this->repository->exists_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected the campaign existence check to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame( 'Failed to check campaign "7" existence.', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function insert_persists_campaign_and_returns_persisted_snapshot(): void {

		$campaign = $this->make_campaign(
			id: 7,
			title: 'New campaign',
			accepts_donations: true,
			currency_code: 'USD',
			target_amount: 12_300,
		);
		$this->database
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'version' => 1,
					'title' => 'New campaign',
					'accepts_donations' => true,
					'currency_code' => 'USD',
					'target_amount' => 12_300,
					'collected_amount' => 0,
					'donations_count' => 0,
					'created_at' => self::CURRENT_TIME,
					'updated_at' => null,
				],
			);

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				$this->make_campaign_row(
					id: 7,
					title: 'Persisted campaign',
					accepts_donations: true,
					currency_code: 'USD',
					target_amount: 12_300,
				),
			);

		$persisted = $this->repository->insert( $campaign );

		self::assertSame( 7, $persisted->get_id()->get_value() );
		self::assertSame( 1, $persisted->get_version()->get_value() );
		self::assertSame( 'Persisted campaign', $persisted->get_title() );
		self::assertTrue( $persisted->accepts_donations() );
		self::assertSame( 'USD', $persisted->get_target()->get_currency()->get_code() );
		self::assertSame( 12_300, $persisted->get_target()->get_amount()?->get_value() );
	}

	#[Test]
	public function insert_throws_for_invalid_campaign_id(): void {

		$campaign = $this->make_campaign( id: '123e4567-e89b-12d3-a456-426614174000' );

		try {
			$this->repository->insert( $campaign );
			self::fail( 'Expected the campaign insert to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame(
				'Campaign ID must be a positive integer. Given: 123e4567-e89b-12d3-a456-426614174000.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function insert_throws_when_campaign_version_is_not_initial(): void {

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot insert campaign "7": version must be initial. Given: 2.' );

		$this->repository->insert( $this->make_campaign( id: 7, version: 2 ) );
	}

	#[Test]
	public function insert_throws_specific_exception_for_duplicate_campaign_id(): void {

		$database_exception = new FakeDatabaseDuplicateKeyException( 'Duplicate key.' );

		$this->database
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) )
			->andThrow( $database_exception );

		try {
			$this->repository->insert( $this->make_campaign( id: 7 ) );
			self::fail( 'Expected the duplicate campaign insert to throw.' );
		} catch ( CampaignAlreadyExistsException $exception ) {
			self::assertSame( 'Cannot insert campaign "7": campaign already exists.', $exception->getMessage() );
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
			$this->repository->insert( $this->make_campaign( id: 7 ) );
			self::fail( 'Expected the campaign insert to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame( 'Failed to insert campaign "7".', $exception->getMessage() );
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
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Campaign "7" was inserted, but fetching persisted snapshot failed.' );

		$this->repository->insert( $this->make_campaign( id: 7 ) );
	}

	#[Test]
	public function update_persists_next_version_and_returns_persisted_snapshot(): void {

		$campaign = $this->make_campaign(
			id: 7,
			version: 3,
			title: 'Updated campaign',
			accepts_donations: true,
			target_amount: null,
		);
		$this->database
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'title' => 'Updated campaign',
					'accepts_donations' => true,
					'target_amount' => null,
					'updated_at' => self::CURRENT_TIME,
					'version' => 4,
				],
				[
					'id' => 7,
					'version' => 3,
				],
			)
			->andReturn( 1 );

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				$this->make_campaign_row(
					id: 7,
					version: 4,
					title: 'Persisted update',
					accepts_donations: true,
					target_amount: null,
				),
			);

		$persisted = $this->repository->update( $campaign );

		self::assertSame( 4, $persisted->get_version()->get_value() );
		self::assertSame( 'Persisted update', $persisted->get_title() );
		self::assertTrue( $persisted->accepts_donations() );
		self::assertFalse( $persisted->has_target() );
	}

	#[Test]
	public function update_throws_for_invalid_campaign_id(): void {

		$campaign = $this->make_campaign( id: '123e4567-e89b-12d3-a456-426614174000', version: 3 );

		try {
			$this->repository->update( $campaign );
			self::fail( 'Expected the campaign update to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame(
				'Campaign ID must be a positive integer. Given: 123e4567-e89b-12d3-a456-426614174000.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
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
					'id' => 7,
					'version' => 3,
				],
			)
			->andThrow( $database_exception );

		try {
			$this->repository->update( $this->make_campaign( id: 7, version: 3 ) );
			self::fail( 'Expected the campaign update to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame( 'Failed to update campaign "7".', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function update_throws_when_campaign_is_not_found(): void {

		$this->database
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( false );

		$this->expectException( CampaignNotFoundException::class );
		$this->expectExceptionMessage( 'Cannot update campaign "7": persisted record not found.' );

		$this->repository->update( $this->make_campaign( id: 7, version: 3 ) );
	}

	#[Test]
	public function update_throws_when_campaign_version_does_not_match(): void {

		$this->database
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->database
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot update campaign "7": version mismatch.' );

		$this->repository->update( $this->make_campaign( id: 7, version: 3 ) );
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
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Campaign "7" was updated, but fetching persisted snapshot failed.' );

		$this->repository->update( $this->make_campaign( id: 7, version: 3 ) );
	}

	#[Test]
	public function delete_removes_campaign_by_id(): void {

		$this->database
			->shouldReceive( 'delete' )
			->once()
			->with( self::TABLE_NAME, 7 );

		$this->repository->delete( EntityId::create( 7 ) );
	}

	#[Test]
	public function delete_throws_for_invalid_campaign_id(): void {

		try {
			$this->repository->delete(
				EntityId::create( '123e4567-e89b-12d3-a456-426614174000' ),
			);
			self::fail( 'Expected the campaign delete to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame(
				'Campaign ID must be a positive integer. Given: 123e4567-e89b-12d3-a456-426614174000.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function delete_throws_specific_exception_when_campaign_is_not_found(): void {

		$database_exception = new FakeDatabaseRowNotFoundException( 'Row not found.' );

		$this->database
			->shouldReceive( 'delete' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( $database_exception );

		try {
			$this->repository->delete( EntityId::create( 7 ) );
			self::fail( 'Expected the missing campaign delete to throw.' );
		} catch ( CampaignNotFoundException $exception ) {
			self::assertSame( 'Cannot delete campaign "7": persisted record not found.', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function delete_throws_when_database_delete_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'delete' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( $database_exception );

		try {
			$this->repository->delete( EntityId::create( 7 ) );
			self::fail( 'Expected the campaign delete to throw.' );
		} catch ( CampaignRepositoryException $exception ) {
			self::assertSame( 'Failed to delete campaign "7".', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	public static function required_row_field_cases(): iterable {

		yield 'id' => [ 'id', '<unavailable>' ];
		yield 'version' => [ 'version', '7' ];
		yield 'title' => [ 'title', '7' ];
		yield 'accepts donations' => [ 'accepts_donations', '7' ];
		yield 'currency code' => [ 'currency_code', '7' ];
		yield 'target amount' => [ 'target_amount', '7' ];
	}

	public static function invalid_row_field_cases(): iterable {

		yield 'id' => [ 'id', null, '<unavailable>' ];
		yield 'version' => [ 'version', null, '7' ];
		yield 'title' => [ 'title', null, '7' ];
		yield 'accepts donations' => [ 'accepts_donations', null, '7' ];
		yield 'currency code' => [ 'currency_code', null, '7' ];
		yield 'target amount' => [ 'target_amount', 'invalid', '7' ];
	}

	public static function campaign_existence_cases(): iterable {

		yield 'campaign exists' => [ true ];
		yield 'campaign does not exist' => [ false ];
	}
}
