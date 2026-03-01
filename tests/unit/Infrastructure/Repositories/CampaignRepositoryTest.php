<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\DatabaseException;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignRepository::class )]
final class CampaignRepositoryTest extends MockeryTestCase {

	private const TABLE_NAME = 'fundrik_campaigns';

	private DatabaseInterface&MockInterface $db;

	private CampaignFactory $campaign_factory;

	private CampaignRepository $repository;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabaseInterface::class );
		$this->campaign_factory = new CampaignFactory();

		$this->repository = new CampaignRepository( $this->db, $this->campaign_factory );
	}

	#[Test]
	public function find_by_id_maps_row_to_campaign(): void {

		$id = EntityId::create( 7 );

		$row = [
			'id' => '7',
			'version' => '3',
			'title' => 'Hello',
			'is_active' => '1',
			'is_open' => '0',
			'has_target' => '1',
			'target_amount' => '123',
			'target_currency' => 'RUB',
		];

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $row );

		$result = $this->repository->find_by_id( $id );

		self::assertSame( 7, $result->get_id()->get_value() );
		self::assertSame( 3, $result->get_version()->get_value() );
		self::assertSame( 'Hello', $result->get_title() );
		self::assertTrue( $result->is_active() );
		self::assertFalse( $result->is_open() );
		self::assertTrue( $result->has_target() );
		self::assertSame( 123, $result->get_target_money()->get_amount_minor() );
	}

	#[Test]
	public function find_by_id_returns_null_when_not_found(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		$result = $this->repository->find_by_id( $id );

		self::assertNull( $result );
	}

	#[Test]
	public function find_by_id_throws_when_entity_id_is_not_int_compatible(): void {

		$id = EntityId::create( '019b6bcb-2f32-7461-838f-67a1479fbdbe' );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Cannot use campaign ID in persistence: ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
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
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot fetch campaign: persistence error. Given: ID 7.' );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_row_cannot_be_mapped_to_campaign(): void {

		$id = EntityId::create( 7 );

		$row = [
			'id' => '7',
			// 'version' is missing on purpose.
			'title' => 'Hello',
			'is_active' => '1',
			'is_open' => '0',
			'has_target' => '1',
			'target_amount' => '123',
			'target_currency' => 'RUB',
		];

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $row );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot map campaign row to entity. Given: ID 7.' );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_all_maps_all_rows_to_campaigns_in_order(): void {

		$rows = [
			[
				'id' => '7',
				'version' => '3',
				'title' => 'Hello',
				'is_active' => '1',
				'is_open' => '0',
				'has_target' => '1',
				'target_amount' => '123',
				'target_currency' => 'RUB',
			],
			[
				'id' => '8',
				'version' => '1',
				'title' => 'World',
				'is_active' => '0',
				'is_open' => '1',
				'has_target' => '0',
				'target_amount' => '0',
				'target_currency' => 'RUB',
			],
		];

		$this->db
			->shouldReceive( 'get_all' )
			->once()
			->with( self::TABLE_NAME )
			->andReturn( $rows );

		$result = $this->repository->find_all();

		self::assertCount( 2, $result );

		self::assertSame( 7, $result[0]->get_id()->get_value() );
		self::assertSame( 3, $result[0]->get_version()->get_value() );
		self::assertSame( 'Hello', $result[0]->get_title() );
		self::assertTrue( $result[0]->is_active() );
		self::assertFalse( $result[0]->is_open() );
		self::assertTrue( $result[0]->has_target() );
		self::assertSame( 123, $result[0]->get_target_money()->get_amount_minor() );

		self::assertSame( 8, $result[1]->get_id()->get_value() );
		self::assertSame( 1, $result[1]->get_version()->get_value() );
		self::assertSame( 'World', $result[1]->get_title() );
		self::assertFalse( $result[1]->is_active() );
		self::assertTrue( $result[1]->is_open() );
		self::assertFalse( $result[1]->has_target() );
		self::assertSame( 0, $result[1]->get_target_money()->get_amount_minor() );
	}

	#[Test]
	public function find_all_returns_empty_list_when_no_rows(): void {

		$this->db
			->shouldReceive( 'get_all' )
			->once()
			->with( self::TABLE_NAME )
			->andReturn( [] );

		$result = $this->repository->find_all();

		self::assertSame( [], $result );
	}

	#[Test]
	public function find_all_throws_when_database_query_fails(): void {

		$this->db
			->shouldReceive( 'get_all' )
			->once()
			->with( self::TABLE_NAME )
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot fetch campaigns: persistence error.' );

		$this->repository->find_all();
	}

	#[Test]
	public function find_all_throws_when_any_row_cannot_be_mapped_to_campaign(): void {

		$rows = [
			[
				'id' => '7',
				// 'version' is missing on purpose.
				'title' => 'Hello',
				'is_active' => '1',
				'is_open' => '0',
				'has_target' => '1',
				'target_amount' => '123',
				'target_currency' => 'RUB',
			],
		];

		$this->db
			->shouldReceive( 'get_all' )
			->once()
			->with( self::TABLE_NAME )
			->andReturn( $rows );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot map campaign row to entity. Given: ID 7.' );

		$this->repository->find_all();
	}

	#[Test]
	public function exists_by_id_returns_true_when_row_exists(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		$result = $this->repository->exists_by_id( $id );

		self::assertTrue( $result );
	}

	#[Test]
	public function exists_by_id_returns_false_when_row_does_not_exist(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( false );

		$result = $this->repository->exists_by_id( $id );

		self::assertFalse( $result );
	}

	#[Test]
	public function exists_by_id_throws_when_entity_id_is_not_int_compatible(): void {

		$id = EntityId::create( '019b6bcb-2f32-7461-838f-67a1479fbdbe' );

		$this->db->shouldNotReceive( 'exists_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Cannot use campaign ID in persistence: ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->exists_by_id( $id );
	}

	#[Test]
	public function exists_by_id_throws_when_database_query_fails(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot check campaign existence: persistence error. Given: ID 7.' );

		$this->repository->exists_by_id( $id );
	}

	#[Test]
	public function insert_inserts_row_and_returns_persisted_campaign_snapshot(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'title' => 'Hello',
					'is_active' => true,
					'is_open' => false,
					'has_target' => true,
					'target_amount' => 123,
					'target_currency' => 'RUB',
					'version' => 3,
				],
			);

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				[
					'id' => '7',
					'version' => '3',
					'title' => 'Hello',
					'is_active' => '1',
					'is_open' => '0',
					'has_target' => '1',
					'target_amount' => '123',
					'target_currency' => 'RUB',
				],
			);

		$result = $this->repository->insert( $campaign );

		self::assertSame( 7, $result->get_id()->get_value() );
		self::assertSame( 3, $result->get_version()->get_value() );
		self::assertSame( 'Hello', $result->get_title() );
		self::assertTrue( $result->is_active() );
		self::assertFalse( $result->is_open() );
		self::assertTrue( $result->has_target() );
		self::assertSame( 123, $result->get_target_money()->get_amount_minor() );
	}

	#[Test]
	public function insert_throws_when_campaign_entity_id_is_not_int_compatible(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: '019b6bcb-2f32-7461-838f-67a1479fbdbe',
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db->shouldNotReceive( 'insert' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Cannot use campaign ID in persistence: ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->insert( $campaign );
	}

	#[Test]
	public function insert_throws_when_database_insert_fails(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'title' => 'Hello',
					'is_active' => true,
					'is_open' => false,
					'has_target' => true,
					'target_amount' => 123,
					'target_currency' => 'RUB',
					'version' => 3,
				],
			)
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot insert campaign: persistence error. Given: ID 7.' );

		$this->repository->insert( $campaign );
	}

	#[Test]
	public function insert_throws_when_campaign_is_not_found_after_insert(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'title' => 'Hello',
					'is_active' => true,
					'is_open' => false,
					'has_target' => true,
					'target_amount' => 123,
					'target_currency' => 'RUB',
					'version' => 3,
				],
			);

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( null );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot fetch campaign after insert: persisted record not found. Given: ID 7.' );

		$this->repository->insert( $campaign );
	}

	#[Test]
	public function update_updates_row_with_next_version_and_returns_persisted_campaign_snapshot(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'title' => 'Hello',
					'is_active' => true,
					'is_open' => false,
					'has_target' => true,
					'target_amount' => 123,
					'target_currency' => 'RUB',
					'version' => 4,
				],
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
			->andReturn(
				[
					'id' => '7',
					'version' => '4',
					'title' => 'Hello',
					'is_active' => '1',
					'is_open' => '0',
					'has_target' => '1',
					'target_amount' => '123',
					'target_currency' => 'RUB',
				],
			);

		$result = $this->repository->update( $campaign );

		self::assertSame( 7, $result->get_id()->get_value() );
		self::assertSame( 4, $result->get_version()->get_value() );
		self::assertSame( 'Hello', $result->get_title() );
		self::assertTrue( $result->is_active() );
		self::assertFalse( $result->is_open() );
		self::assertTrue( $result->has_target() );
		self::assertSame( 123, $result->get_target_money()->get_amount_minor() );
	}

	#[Test]
	public function update_throws_when_campaign_entity_id_is_not_int_compatible(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: '019b6bcb-2f32-7461-838f-67a1479fbdbe',
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db->shouldNotReceive( 'update' );
		$this->db->shouldNotReceive( 'exists_by_id' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Cannot use campaign ID in persistence: ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_database_update_fails(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'title' => 'Hello',
					'is_active' => true,
					'is_open' => false,
					'has_target' => true,
					'target_amount' => 123,
					'target_currency' => 'RUB',
					'version' => 4,
				],
				[
					'id' => 7,
					'version' => 3,
				],
			)
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'exists_by_id' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot update campaign: persistence error. Given: ID 7.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_no_rows_affected_and_campaign_is_missing(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

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

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot update campaign: persisted record not found. Given: ID 7.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_no_rows_affected_and_version_mismatch(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

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
		$this->expectExceptionMessage( 'Cannot update campaign: version mismatch. Given: ID 7, expected version 3.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_campaign_is_not_found_after_update(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

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
		$this->expectExceptionMessage( 'Cannot fetch campaign after update: persisted record not found. Given: ID 7.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function update_throws_when_updated_row_cannot_be_mapped_to_campaign(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andReturn( 1 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				[
					'id' => '7',
					// 'version' is missing on purpose.
					'title' => 'Hello',
					'is_active' => '1',
					'is_open' => '0',
					'has_target' => '1',
					'target_amount' => '123',
					'target_currency' => 'RUB',
				],
			);

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot map campaign row to entity. Given: ID 7.' );

		$this->repository->update( $campaign );
	}

	#[Test]
	public function save_inserts_when_campaign_does_not_exist(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( false );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'title' => 'Hello',
					'is_active' => true,
					'is_open' => false,
					'has_target' => true,
					'target_amount' => 123,
					'target_currency' => 'RUB',
					'version' => 3,
				],
			);

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				[
					'id' => '7',
					'version' => '3',
					'title' => 'Hello',
					'is_active' => '1',
					'is_open' => '0',
					'has_target' => '1',
					'target_amount' => '123',
					'target_currency' => 'RUB',
				],
			);

		$outcome = $this->repository->save( $campaign );

		self::assertInstanceOf( CampaignRepositorySaveOutcome::class, $outcome );
		self::assertSame( CampaignRepositorySaveResult::Inserted, $outcome->result );
		self::assertSame( 7, $outcome->campaign->get_id()->get_value() );
		self::assertSame( 3, $outcome->campaign->get_version()->get_value() );
	}

	#[Test]
	public function save_updates_when_campaign_exists(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => 7,
					'title' => 'Hello',
					'is_active' => true,
					'is_open' => false,
					'has_target' => true,
					'target_amount' => 123,
					'target_currency' => 'RUB',
					'version' => 4,
				],
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
			->andReturn(
				[
					'id' => '7',
					'version' => '4',
					'title' => 'Hello',
					'is_active' => '1',
					'is_open' => '0',
					'has_target' => '1',
					'target_amount' => '123',
					'target_currency' => 'RUB',
				],
			);

		$outcome = $this->repository->save( $campaign );

		self::assertInstanceOf( CampaignRepositorySaveOutcome::class, $outcome );
		self::assertSame( CampaignRepositorySaveResult::Updated, $outcome->result );
		self::assertSame( 7, $outcome->campaign->get_id()->get_value() );
		self::assertSame( 4, $outcome->campaign->get_version()->get_value() );
	}

	#[Test]
	public function save_throws_when_existence_check_fails(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'insert' );
		$this->db->shouldNotReceive( 'update' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot check campaign existence: persistence error. Given: ID 7.' );

		$this->repository->save( $campaign );
	}

	#[Test]
	public function save_throws_when_insert_fails(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( false );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot insert campaign: persistence error. Given: ID 7.' );

		$this->repository->save( $campaign );
	}

	#[Test]
	public function save_throws_when_update_fails(): void {

		$campaign = $this->campaign_factory->create_from_primitives(
			id: 7,
			version: 3,
			title: 'Hello',
			is_active: true,
			is_open: false,
			has_target: true,
			target_amount: 123,
		target_currency: 'RUB',
		);

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( true );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot update campaign: persistence error. Given: ID 7.' );

		$this->repository->save( $campaign );
	}

	#[Test]
	public function delete_deletes_row_by_int_id(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'delete' )
			->once()
			->with( self::TABLE_NAME, 7 );

		$this->repository->delete( $id );

		$this->addToAssertionCount( 1 );
	}

	#[Test]
	public function delete_throws_when_entity_id_is_not_int_compatible(): void {

		$id = EntityId::create( '019b6bcb-2f32-7461-838f-67a1479fbdbe' );

		$this->db->shouldNotReceive( 'delete' );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage(
			'Cannot use campaign ID in persistence: ID must be int-compatible. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
		);

		$this->repository->delete( $id );
	}

	#[Test]
	public function delete_throws_when_database_delete_fails(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'delete' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andThrow( new DatabaseException( 'DB failed.' ) );

		$this->expectException( CampaignRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot delete campaign: persistence error. Given: ID 7.' );

		$this->repository->delete( $id );
	}
}

