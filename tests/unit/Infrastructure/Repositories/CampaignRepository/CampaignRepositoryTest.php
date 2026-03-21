<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignNotFoundException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignRepository;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignRepositoryException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignRepository::class )]
#[CoversClass( CampaignNotFoundException::class )]
final class CampaignRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_campaigns';

	private DatabasePort&MockInterface $db;
	private CampaignFactory $campaign_factory;
	private CampaignRepository $repository;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabasePort::class );
		$this->campaign_factory = new CampaignFactory();
		$this->repository = new CampaignRepository( $this->db, $this->campaign_factory );
	}

	#[Test]
	public function find_by_id_maps_row_to_campaign(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $this->campaign_row( id: 7, version: 3, is_open: false, target_amount: 123 ) );

		$result = $this->repository->find_by_id( $id );

		self::assertSame( 7, $result?->get_id()->get_value() );
		self::assertSame( 3, $result?->get_version()->get_value() );
		self::assertSame( 'Hello', $result?->get_title() );
		self::assertFalse( $result?->can_receive_donations() );
		self::assertTrue( $result?->has_target() );
		self::assertSame( 123, $result?->get_target()->get_amount()?->get_value() );
		self::assertSame( 'RUB', $result?->get_target()->get_currency()->get_code() );
	}

	#[Test]
	public function find_by_id_returns_campaign_without_target_when_persistence_row_has_no_target(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $this->campaign_row( id: 7, version: 3, is_open: true, target_amount: null ) );

		$result = $this->repository->find_by_id( $id );

		self::assertTrue( $result?->can_receive_donations() );
		self::assertFalse( $result?->has_target() );
		self::assertNull( $result?->get_target()->get_amount() );
		self::assertSame( 'RUB', $result?->get_target()->get_currency()->get_code() );
	}

	#[Test]
	public function find_by_id_returns_null_when_not_found(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		self::assertNull( $this->repository->find_by_id( $id ) );
	}

	#[Test]
	public function find_by_id_throws_when_entity_id_is_not_int_compatible(): void {

		$id = EntityId::create( '019b6bcb-2f32-7461-838f-67a1479fbdbe' );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Campaign ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_database_query_fails(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to fetch campaign "7".' );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_row_cannot_be_mapped_to_campaign(): void {

		$id = EntityId::create( 7 );
		$row = $this->campaign_row( id: 7, version: null );
		unset( $row['version'] );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $row );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to map campaign row "7".' );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function exists_by_id_returns_true_when_row_exists(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		self::assertTrue( $this->repository->exists_by_id( $id ) );
	}

	#[Test]
	public function exists_by_id_returns_false_when_row_does_not_exist(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( false );

		self::assertFalse( $this->repository->exists_by_id( $id ) );
	}

	#[Test]
	public function exists_by_id_throws_when_database_query_fails(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to check campaign "7" existence.' );

		$this->repository->exists_by_id( $id );
	}

	#[Test]
	public function insert_inserts_row_and_returns_persisted_campaign_snapshot(): void {

		$campaign = $this->campaign( id: 7, version: 1, is_open: false, target_amount: 123 );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				Mockery::on(
					static fn ( array $row ): bool => self::matches_insert_row(
						$row,
						id: 7,
						version: 1,
						title: 'Hello',
						is_open: false,
						currency_code: 'RUB',
						target_amount: 123,
					),
				),
			);

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $this->campaign_row( id: 7, version: 1, is_open: false, target_amount: 123 ) );

		$result = $this->repository->insert( $campaign );

		self::assertSame( 7, $result->get_id()->get_value() );
		self::assertSame( 1, $result->get_version()->get_value() );
		self::assertFalse( $result->can_receive_donations() );
		self::assertSame( 123, $result->get_target()->get_amount()?->get_value() );
	}

	#[Test]
	public function insert_throws_when_campaign_entity_id_is_not_int_compatible(): void {

		$campaign = $this->campaign( id: '019b6bcb-2f32-7461-838f-67a1479fbdbe' );

		$this->db->shouldNotReceive( 'insert' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Campaign ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->insert( $campaign );
	}

	#[Test]
	public function insert_throws_when_version_is_not_initial(): void {

		$campaign = $this->campaign( id: 7, version: 2, is_open: false, target_amount: 123 );

		$this->db->shouldNotReceive( 'insert' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Cannot insert campaign "7": version must be initial. Given: 2.',
		);

		$this->repository->insert( $campaign );
	}

	#[Test]
	public function insert_throws_when_database_insert_fails(): void {

		$campaign = $this->campaign( id: 7, version: 1, is_open: false, target_amount: 123 );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				Mockery::on(
					static fn ( array $row ): bool => self::matches_insert_row(
						$row,
						id: 7,
						version: 1,
						title: 'Hello',
						is_open: false,
						currency_code: 'RUB',
						target_amount: 123,
					),
				),
			)
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to insert campaign "7".' );

		$this->repository->insert( $campaign );
	}

	#[Test]
	public function insert_throws_when_campaign_is_not_found_after_insert(): void {

		$campaign = $this->campaign( id: 7, version: 1, is_open: false, target_amount: 123 );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				Mockery::on(
					static fn ( array $row ): bool => self::matches_insert_row(
						$row,
						id: 7,
						version: 1,
						title: 'Hello',
						is_open: false,
						currency_code: 'RUB',
						target_amount: 123,
					),
				),
			);

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Campaign "7" was inserted, but fetching persisted snapshot failed.' );

		$this->repository->insert( $campaign );
	}

	#[Test]
	public function update_updates_row_with_next_version_and_returns_persisted_campaign_snapshot(): void {

		$campaign = $this->campaign( id: 7, version: 3, is_open: true, target_amount: null );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				Mockery::on(
					static fn ( array $row ): bool => self::matches_update_row(
						$row,
						title: 'Hello',
						is_open: true,
						target_amount: null,
						version: 4,
					),
				),
				[
					'id' => 7,
					'version' => 3,
				],
			)
			->andReturn( 1 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $this->campaign_row( id: 7, version: 4, is_open: true, target_amount: null ) );

		$result = $this->repository->update( $campaign );

		self::assertSame( 4, $result->get_version()->get_value() );
		self::assertTrue( $result->can_receive_donations() );
		self::assertFalse( $result->has_target() );
		self::assertNull( $result->get_target()->get_amount() );
	}

	#[Test]
	public function update_throws_when_campaign_entity_id_is_not_int_compatible(): void {

		$campaign = $this->campaign( id: '019b6bcb-2f32-7461-838f-67a1479fbdbe' );

		$this->db->shouldNotReceive( 'update' );
		$this->db->shouldNotReceive( 'exists_by_id' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Campaign ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_database_update_fails(): void {

		$campaign = $this->campaign( id: 7, version: 3, is_open: true, target_amount: null );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				Mockery::on(
					static fn ( array $row ): bool => self::matches_update_row(
						$row,
						title: 'Hello',
						is_open: true,
						target_amount: null,
						version: 4,
					),
				),
				[
					'id' => 7,
					'version' => 3,
				],
			)
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'exists_by_id' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to update campaign "7".' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_no_rows_affected_and_campaign_is_missing(): void {

		$campaign = $this->campaign( id: 7, version: 3, is_open: false, target_amount: 123 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( false );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignNotFoundException::class );
		$this->expectExceptionMessage( 'Cannot update campaign "7": persisted record not found.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_no_rows_affected_and_version_mismatch(): void {

		$campaign = $this->campaign( id: 7, version: 3, is_open: false, target_amount: 123 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot update campaign "7": version mismatch.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_campaign_is_not_found_after_update(): void {

		$campaign = $this->campaign( id: 7, version: 3, is_open: false, target_amount: 123 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andReturn( 1 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Campaign "7" was updated, but fetching persisted snapshot failed.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function delete_deletes_row_by_int_id(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		$this->db
			->shouldReceive( 'delete' )
			->once()
			->with( self::TABLE_NAME, 7 );

		$this->repository->delete( $id );

		$this->addToAssertionCount( 1 );
	}

	#[Test]
	public function delete_throws_when_campaign_does_not_exist(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( false );

		$this->db->shouldNotReceive( 'delete' );

		$this->expectException( CampaignNotFoundException::class );
		$this->expectExceptionMessage( 'Cannot delete campaign "7": persisted record not found.' );

		$this->repository->delete( $id );
	}

	#[Test]
	public function delete_throws_when_entity_id_is_not_int_compatible(): void {

		$id = EntityId::create( '019b6bcb-2f32-7461-838f-67a1479fbdbe' );

		$this->db->shouldNotReceive( 'exists_by_id' );
		$this->db->shouldNotReceive( 'delete' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Campaign ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->delete( $id );
	}

	#[Test]
	public function delete_throws_when_database_delete_fails(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		$this->db
			->shouldReceive( 'delete' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to delete campaign "7".' );

		$this->repository->delete( $id );
	}

	private function campaign(
		int|string $id = 7,
		int $version = 3,
		string $title = 'Hello',
		bool $is_open = false,
		string $currency_code = 'RUB',
		?int $target_amount = 123,
	): Campaign {

		return $this->campaign_factory->create_from_primitives(
			id: $id,
			version: $version,
			title: $title,
			is_open: $is_open,
			currency_code: $currency_code,
			target_amount: $target_amount,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function campaign_row(
		int $id = 7,
		?int $version = 3,
		string $title = 'Hello',
		bool $is_open = false,
		string $currency_code = 'RUB',
		?int $target_amount = 123,
	): array {

		return [
			'id' => (string) $id,
			'version' => (string) $version,
			'title' => $title,
			'is_open' => $is_open ? '1' : '0',
			'currency_code' => $currency_code,
			'target_amount' => $target_amount === null ? null : (string) $target_amount,
			'created_at' => '2026-03-21 10:00:00',
			'updated_at' => null,
		];
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function matches_insert_row(
		array $row,
		int $id,
		int $version,
		string $title,
		bool $is_open,
		string $currency_code,
		?int $target_amount,
	): bool {

		return ( $row['id'] ?? null ) === $id
			&& ( $row['version'] ?? null ) === $version
			&& ( $row['title'] ?? null ) === $title
			&& ( $row['is_open'] ?? null ) === $is_open
			&& ( $row['currency_code'] ?? null ) === $currency_code
			&& ( $row['target_amount'] ?? null ) === $target_amount
			&& is_string( $row['created_at'] ?? null )
			&& preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['created_at'] ) === 1
			&& ( $row['updated_at'] ?? null ) === null;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function matches_update_row(
		array $row,
		string $title,
		bool $is_open,
		?int $target_amount,
		int $version,
	): bool {

		return ( $row['title'] ?? null ) === $title
			&& ( $row['is_open'] ?? null ) === $is_open
			&& ( $row['target_amount'] ?? null ) === $target_amount
			&& ( $row['version'] ?? null ) === $version
			&& is_string( $row['updated_at'] ?? null )
			&& preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row['updated_at'] ) === 1
			&& ! array_key_exists( 'currency_code', $row );
	}
}
