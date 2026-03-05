<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories;

use DateTimeImmutable;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepositoryException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationRepository::class )]
final class DonationRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_donations';

	private const string DONATION_ID = '01956b66-c80b-7f0e-b8d4-4c4f9f7d5531';

	private DatabasePort&MockInterface $db;

	private DonationFactory $donation_factory;

	private DonationRepository $repository;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabasePort::class );
		$this->donation_factory = new DonationFactory();

		$this->repository = new DonationRepository( $this->db, $this->donation_factory );
	}

	#[Test]
	public function find_by_id_maps_row_to_donation(): void {

		$id = EntityId::create( self::DONATION_ID );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( self::make_row() );

		$result = $this->repository->find_by_id( $id );

		self::assertSame( self::DONATION_ID, $result?->get_id()->get_value() );
		self::assertSame( 3, $result?->get_version()->get_value() );
		self::assertSame( 77, $result?->get_campaign_id()->get_value() );
		self::assertSame( 1_000, $result?->get_money()->get_amount_minor() );
		self::assertSame( 'USD', $result?->get_money()->get_currency() );
		self::assertSame( 'captured', $result?->get_status()->value );
	}

	#[Test]
	public function find_by_id_returns_null_when_not_found(): void {

		$id = EntityId::create( self::DONATION_ID );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( null );

		self::assertNull( $this->repository->find_by_id( $id ) );
	}

	#[Test]
	public function find_by_id_throws_when_database_query_fails(): void {

		$id = EntityId::create( self::DONATION_ID );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to fetch donation "%s".', self::DONATION_ID ) );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_entity_id_is_not_uuid_compatible(): void {

		$id = EntityId::create( 7 );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage( 'Cannot use donation ID in persistence: ID must be UUID-compatible. Given: 7.' );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_row_cannot_be_mapped_to_donation(): void {

		$id = EntityId::create( self::DONATION_ID );

		$row = self::make_row();
		unset( $row['amount_minor'] );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( $row );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf( 'Failed to map donation row. Given: ID %s.', self::DONATION_ID ),
		);

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_all_maps_rows_to_donations(): void {

		$this->db
			->shouldReceive( 'get_all' )
			->once()
			->with( self::TABLE_NAME )
			->andReturn(
				[
					self::make_row(),
					self::make_row(
						[
							'id' => '01956b66-c80b-7f0e-b8d4-4c4f9f7d5532',
							'status' => 'pending',
							'captured_at' => null,
							'status_changed_at' => null,
						],
					),
				],
			);

		$result = $this->repository->find_all();

		self::assertCount( 2, $result );
		self::assertSame( self::DONATION_ID, $result[0]->get_id()->get_value() );
		self::assertSame( '01956b66-c80b-7f0e-b8d4-4c4f9f7d5532', $result[1]->get_id()->get_value() );
	}

	#[Test]
	public function find_all_throws_when_database_query_fails(): void {

		$this->db
			->shouldReceive( 'get_all' )
			->once()
			->with( self::TABLE_NAME )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to fetch donations.' );

		$this->repository->find_all();
	}

	#[Test]
	public function find_all_by_campaign_id_returns_only_matching_donations(): void {

		$campaign_id = EntityId::create( 77 );

		$this->db
			->shouldReceive( 'get_all_by_column' )
			->once()
			->with( self::TABLE_NAME, 'campaign_id', 77 )
			->andReturn(
				[
					self::make_row( [ 'campaign_id' => '77' ] ),
				],
			);

		$result = $this->repository->find_all_by_campaign_id( $campaign_id );

		self::assertCount( 1, $result );
		self::assertSame( self::DONATION_ID, $result[0]->get_id()->get_value() );
	}

	#[Test]
	public function find_all_by_campaign_id_throws_when_database_query_fails(): void {

		$campaign_id = EntityId::create( 77 );

		$this->db
			->shouldReceive( 'get_all_by_column' )
			->once()
			->with( self::TABLE_NAME, 'campaign_id', 77 )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage( 'Failed to fetch donations for campaign "77".' );

		$this->repository->find_all_by_campaign_id( $campaign_id );
	}

	#[Test]
	public function find_all_by_campaign_id_throws_when_campaign_id_is_not_int_compatible(): void {

		$campaign_id = EntityId::create( '01956b66-c80b-7f0e-b8d4-4c4f9f7d5599' );

		$this->db->shouldNotReceive( 'get_all_by_column' );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			'Cannot use campaign ID in donation persistence: ID must be int-compatible. Given: 01956b66-c80b-7f0e-b8d4-4c4f9f7d5599.',
		);

		$this->repository->find_all_by_campaign_id( $campaign_id );
	}

	#[Test]
	public function exists_by_id_returns_true_when_row_exists(): void {

		$id = EntityId::create( self::DONATION_ID );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( true );

		self::assertTrue( $this->repository->exists_by_id( $id ) );
	}

	#[Test]
	public function exists_by_id_throws_when_database_query_fails(): void {

		$id = EntityId::create( self::DONATION_ID );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to check donation "%s" existence.', self::DONATION_ID ) );

		$this->repository->exists_by_id( $id );
	}

	#[Test]
	public function insert_inserts_row_and_returns_persisted_snapshot(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 1, campaign_id: 77 );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => self::DONATION_ID,
					'version' => 1,
					'campaign_id' => 77,
					'amount_minor' => 1_000,
					'currency' => 'USD',
					'status' => 'pending',
					'created_at' => '2026-01-01 10:00:00.000000',
					'captured_at' => null,
					'status_changed_at' => null,
				],
			);

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn(
				self::make_row(
					[
						'version' => '1',
						'status' => 'pending',
						'captured_at' => null,
						'status_changed_at' => null,
					],
				),
			);

		$result = $this->repository->insert( $donation );

		self::assertSame( self::DONATION_ID, $result->get_id()->get_value() );
		self::assertSame( 1, $result->get_version()->get_value() );
		self::assertSame( 'pending', $result->get_status()->value );
	}

	#[Test]
	public function insert_throws_when_database_insert_fails(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 1, campaign_id: 77 );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to insert donation "%s".', self::DONATION_ID ) );

		$this->repository->insert( $donation );
	}

	#[Test]
	public function insert_throws_when_record_is_not_found_after_insert(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 1, campaign_id: 77 );

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( null );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf( 'Donation "%s" was inserted, but fetching persisted snapshot failed.', self::DONATION_ID ),
		);

		$this->repository->insert( $donation );
	}

	#[Test]
	public function update_updates_row_with_next_version_and_returns_persisted_snapshot(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 3, campaign_id: 77 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'id' => self::DONATION_ID,
					'version' => 4,
					'campaign_id' => 77,
					'amount_minor' => 1_000,
					'currency' => 'USD',
					'status' => 'pending',
					'created_at' => '2026-01-01 10:00:00.000000',
					'captured_at' => null,
					'status_changed_at' => null,
				],
				[
					'id' => self::DONATION_ID,
					'version' => 3,
				],
			)
			->andReturn( 1 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn(
				self::make_row(
					[
						'version' => '4',
						'status' => 'pending',
						'captured_at' => null,
						'status_changed_at' => null,
					],
				),
			);

		$result = $this->repository->update( $donation );

		self::assertSame( self::DONATION_ID, $result->get_id()->get_value() );
		self::assertSame( 4, $result->get_version()->get_value() );
	}

	#[Test]
	public function update_throws_when_database_update_fails(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 3, campaign_id: 77 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ), Mockery::type( 'array' ) )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->db->shouldNotReceive( 'exists_by_id' );
		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to update donation "%s".', self::DONATION_ID ) );

		$this->repository->update( $donation );
	}

	#[Test]
	public function update_throws_when_no_rows_affected_and_donation_is_missing(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 3, campaign_id: 77 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( false );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf( 'Cannot update donation "%s": persisted record not found.', self::DONATION_ID ),
		);

		$this->repository->update( $donation );
	}

	#[Test]
	public function update_throws_when_no_rows_affected_and_version_mismatch(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 3, campaign_id: 77 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andReturn( 0 );

		$this->db
			->shouldReceive( 'exists_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( true );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf( 'Cannot update donation "%s": version mismatch.', self::DONATION_ID ),
		);

		$this->repository->update( $donation );
	}

	#[Test]
	public function update_throws_when_record_is_not_found_after_update(): void {

		$donation = self::make_pending_donation( id: self::DONATION_ID, version: 3, campaign_id: 77 );

		$this->db
			->shouldReceive( 'update' )
			->once()
			->andReturn( 1 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( null );

		$this->expectException( DonationRepositoryException::class );
		$this->expectExceptionMessage(
			sprintf( 'Donation "%s" was updated, but fetching persisted snapshot failed.', self::DONATION_ID ),
		);

		$this->repository->update( $donation );
	}

	/**
	 * Returns a normalized donation row for repository tests.
	 *
	 * @param array<string, int|string|null> $overrides Row field overrides.
	 *
	 * @return array<string, int|string|null>
	 */
	private static function make_row( array $overrides = [] ): array {

		return $overrides + [
			'id' => self::DONATION_ID,
			'version' => '3',
			'campaign_id' => '77',
			'amount_minor' => '1000',
			'currency' => 'USD',
			'status' => 'captured',
			'created_at' => '2026-01-01 10:00:00.000000',
			'captured_at' => '2026-01-01 11:00:00.000000',
			'status_changed_at' => '2026-01-01 11:00:00.000000',
		];
	}

	private static function make_pending_donation( string $id, int $version, int $campaign_id ): Donation {

		return ( new DonationFactory() )->create_from_primitives(
			id: $id,
			version: $version,
			campaign_id: $campaign_id,
			amount_minor: 1_000,
			currency: 'USD',
			status: 'pending',
			created_at: new DateTimeImmutable( '2026-01-01T10:00:00+00:00' ),
		);
	}
}
