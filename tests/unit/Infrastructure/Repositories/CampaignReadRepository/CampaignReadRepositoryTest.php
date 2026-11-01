<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\CampaignReadRepository;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidUtcDateTimeException;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadRepository;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignReadRepository::class )]
#[UsesClass( CampaignId::class )]
final class CampaignReadRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_campaigns';

	private DatabasePort&MockInterface $database;

	private CampaignReadRepository $repository;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->repository = new CampaignReadRepository( $this->database );
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
					accepts_donations: false,
					target_amount: 123,
					collected_amount: 550,
					donations_count: 2,
					updated_at: '2026-03-22 11:15:00',
				),
			);

		$campaign = $this->repository->find_by_id( EntityId::create( 7 ) );

		self::assertNotNull( $campaign );
		self::assertSame( 7, $campaign->get_id() );
		self::assertSame( 'Campaign title', $campaign->get_title() );
		self::assertFalse( $campaign->accepts_donations() );
		self::assertSame( 'RUB', $campaign->get_currency_code() );
		self::assertTrue( $campaign->has_target() );
		self::assertSame( 123, $campaign->get_target_amount() );
		self::assertSame( 550, $campaign->get_collected_amount() );
		self::assertSame( 2, $campaign->get_donations_count() );
		self::assertSame( '2026-03-21 10:00:00', $campaign->get_created_at()->format( 'Y-m-d H:i:s' ) );
		self::assertSame( '2026-03-22 11:15:00', $campaign->get_updated_at()?->format( 'Y-m-d H:i:s' ) );
	}

	#[Test]
	public function find_by_id_maps_nullable_fields(): void {

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				$this->make_campaign_row(
					id: 7,
					accepts_donations: true,
					target_amount: null,
					updated_at: null,
				),
			);

		$campaign = $this->repository->find_by_id( EntityId::create( 7 ) );

		self::assertNotNull( $campaign );
		self::assertTrue( $campaign->accepts_donations() );
		self::assertFalse( $campaign->has_target() );
		self::assertNull( $campaign->get_target_amount() );
		self::assertSame( 0, $campaign->get_collected_amount() );
		self::assertSame( 0, $campaign->get_donations_count() );
		self::assertNull( $campaign->get_updated_at() );
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
		} catch ( CampaignReadException $exception ) {
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
		} catch ( CampaignReadException $exception ) {
			self::assertSame( 'Failed to retrieve campaign "7".', $exception->getMessage() );
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
			self::fail( 'Expected the campaign lookup to throw.' );
		} catch ( CampaignReadException $exception ) {
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
		int|string|null $invalid_value,
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
			self::fail( 'Expected the campaign lookup to throw.' );
		} catch ( CampaignReadException $exception ) {
			self::assertSame(
				sprintf( 'Failed to map campaign row "%s".', $row_id_in_message ),
				$exception->getMessage(),
			);
			self::assertInstanceOf( ArrayExtractionException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	#[DataProvider( 'invalid_datetime_cases' )]
	public function find_by_id_throws_when_row_has_invalid_datetime( string $field ): void {

		$row = $this->make_campaign_row( id: 7 );
		$row[ $field ] = 'invalid-date';

		$this->database
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $row );

		try {
			$this->repository->find_by_id( EntityId::create( 7 ) );
			self::fail( 'Expected the campaign lookup to throw.' );
		} catch ( CampaignReadException $exception ) {
			self::assertSame( 'Failed to map campaign row "7".', $exception->getMessage() );
			self::assertInstanceOf( InvalidUtcDateTimeException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_by_ids_returns_mapped_campaigns_for_unique_ids(): void {

		$this->database
			->shouldReceive( 'get_all_by_ids' )
			->once()
			->with( self::TABLE_NAME, 7, 8 )
			->andReturn(
				[
					$this->make_campaign_row( id: 8, title: 'Second campaign' ),
					$this->make_campaign_row( id: 7, title: 'First campaign' ),
				],
			);

		$campaigns = $this->repository->find_by_ids(
			[
				EntityId::create( 7 ),
				EntityId::create( 8 ),
				EntityId::create( 7 ),
			],
		);

		self::assertCount( 2, $campaigns );
		self::assertSame( 8, $campaigns[0]->get_id() );
		self::assertSame( 'Second campaign', $campaigns[0]->get_title() );
		self::assertSame( 7, $campaigns[1]->get_id() );
		self::assertSame( 'First campaign', $campaigns[1]->get_title() );
	}

	#[Test]
	public function find_by_ids_returns_empty_array_for_empty_ids(): void {

		self::assertSame( [], $this->repository->find_by_ids( [] ) );
	}

	#[Test]
	public function find_by_ids_throws_for_invalid_campaign_id(): void {

		try {
			$this->repository->find_by_ids(
				[
					EntityId::create( 7 ),
					EntityId::create( '123e4567-e89b-12d3-a456-426614174000' ),
				],
			);
			self::fail( 'Expected the campaign lookup to throw.' );
		} catch ( CampaignReadException $exception ) {
			self::assertSame(
				'Campaign ID must be a positive integer. Given: 123e4567-e89b-12d3-a456-426614174000.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidCampaignIdException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_by_ids_throws_when_database_lookup_fails(): void {

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'get_all_by_ids' )
			->once()
			->with( self::TABLE_NAME, 7, 8 )
			->andThrow( $database_exception );

		try {
			$this->repository->find_by_ids(
				[ EntityId::create( 7 ), EntityId::create( 8 ) ],
			);
			self::fail( 'Expected the campaign lookup to throw.' );
		} catch ( CampaignReadException $exception ) {
			self::assertSame( 'Failed to retrieve campaigns.', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	public static function required_row_field_cases(): iterable {

		yield 'id' => [ 'id', '<unavailable>' ];
		yield 'title' => [ 'title', '7' ];
		yield 'accepts donations' => [ 'accepts_donations', '7' ];
		yield 'currency code' => [ 'currency_code', '7' ];
		yield 'target amount' => [ 'target_amount', '7' ];
		yield 'collected amount' => [ 'collected_amount', '7' ];
		yield 'donations count' => [ 'donations_count', '7' ];
		yield 'created at' => [ 'created_at', '7' ];
		yield 'updated at' => [ 'updated_at', '7' ];
	}

	public static function invalid_row_field_cases(): iterable {

		yield 'id' => [ 'id', null, '<unavailable>' ];
		yield 'title' => [ 'title', null, '7' ];
		yield 'accepts donations' => [ 'accepts_donations', null, '7' ];
		yield 'currency code' => [ 'currency_code', null, '7' ];
		yield 'target amount' => [ 'target_amount', 'invalid', '7' ];
		yield 'collected amount' => [ 'collected_amount', null, '7' ];
		yield 'donations count' => [ 'donations_count', null, '7' ];
		yield 'created at' => [ 'created_at', null, '7' ];
		yield 'updated at' => [ 'updated_at', 123, '7' ];
	}

	public static function invalid_datetime_cases(): iterable {

		yield 'created at' => [ 'created_at' ];
		yield 'updated at' => [ 'updated_at' ];
	}
}
