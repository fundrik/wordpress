<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\DonationReadRepository;

use Fundrik\Core\Components\Donations\Application\ReadModels\PaginatedDonations;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\DonationReadRepository\DonationReadException;
use Fundrik\WordPress\Infrastructure\Repositories\DonationReadRepository\DonationReadRepository;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationReadRepository::class )]
#[CoversClass( DonationReadException::class )]
final class DonationReadRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_donations';

	private const string DONATION_ID = '01956b66-c80b-4f0e-b8d4-4c4f9f7d5531';

	private DatabasePort&MockInterface $db;

	private DonationReadRepository $repository;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabasePort::class );
		$this->repository = new DonationReadRepository( $this->db );
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

		self::assertSame( self::DONATION_ID, $result?->get_id() );
		self::assertSame( 77, $result?->get_campaign_id() );
		self::assertSame( 1_000, $result?->get_amount() );
		self::assertSame( 'USD', $result?->get_currency_code() );
		self::assertSame( 'succeeded', $result?->get_status() );
		self::assertSame( '2026-01-01 10:00:00', $result?->get_created_at()->format( 'Y-m-d H:i:s' ) );
		self::assertSame( '2026-01-01 11:00:00', $result?->get_updated_at()?->format( 'Y-m-d H:i:s' ) );
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

		$this->expectException( DonationReadException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to fetch donation "%s".', self::DONATION_ID ) );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_entity_id_is_not_uuid_compatible(): void {

		$id = EntityId::create( 7 );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( DonationReadException::class );
		$this->expectExceptionMessage( 'Donation ID must be a valid UUIDv4. Given: 7.' );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_row_cannot_be_mapped_to_donation(): void {

		$id = EntityId::create( self::DONATION_ID );
		$row = self::make_row();
		unset( $row['amount'] );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, self::DONATION_ID )
			->andReturn( $row );

		$this->expectException( DonationReadException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to map donation row "%s".', self::DONATION_ID ) );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function paginate_maps_rows_to_paginated_donations(): void {

		$this->db
			->shouldReceive( 'paginate' )
			->once()
			->with( self::TABLE_NAME, 2, 20 )
			->andReturn(
				[
					[
						self::make_row(
							[
								'id' => self::DONATION_ID,
								'campaign_id' => 77,
							],
						),
					],
					21,
				],
			);

		$result = $this->repository->paginate( 2, 20 );

		self::assertInstanceOf( PaginatedDonations::class, $result );
		self::assertSame( 2, $result->get_page() );
		self::assertSame( 20, $result->get_per_page() );
		self::assertSame( 21, $result->get_total() );
		self::assertSame( self::DONATION_ID, $result->get_items()[0]->get_id() );
	}

	#[Test]
	public function paginate_throws_when_database_query_fails(): void {

		$this->db
			->shouldReceive( 'paginate' )
			->once()
			->with( self::TABLE_NAME, 1, 20 )
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		$this->expectException( DonationReadException::class );
		$this->expectExceptionMessage( 'Failed to retrieve paginated donations.' );

		$this->repository->paginate( 1, 20 );
	}

	#[Test]
	public function paginate_returns_empty_collection_when_database_has_no_rows(): void {

		$this->db
			->shouldReceive( 'paginate' )
			->once()
			->with( self::TABLE_NAME, 1, 20 )
			->andReturn( [ [], 0 ] );

		$result = $this->repository->paginate( 1, 20 );

		self::assertSame( [], $result->get_items() );
		self::assertSame( 0, $result->get_total() );
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
			'campaign_id' => 77,
			'amount' => 1_000,
			'currency_code' => 'USD',
			'status' => 'succeeded',
			'created_at' => '2026-01-01 10:00:00.000000',
			'updated_at' => '2026-01-01 11:00:00.000000',
		];
	}
}
