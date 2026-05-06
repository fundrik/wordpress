<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\CampaignReadRepository;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadRepository;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignReadRepository::class )]
#[CoversClass( CampaignReadException::class )]
final class CampaignReadRepositoryTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_campaigns';

	private DatabasePort&MockInterface $db;
	private CampaignReadRepository $repository;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabasePort::class );
		$this->repository = new CampaignReadRepository( $this->db );
	}

	#[Test]
	public function find_by_id_maps_row_to_campaign(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				$this->campaign_row(
					id: 7,
					accepts_donations: false,
					target_amount: 123,
					collected_amount: 550,
					donations_count: 2,
					updated_at: '2026-03-22 11:15:00',
				),
			);

		$result = $this->repository->find_by_id( $id );

		self::assertSame( 7, $result?->get_id() );
		self::assertSame( 'Hello', $result?->get_title() );
		self::assertFalse( $result?->accepts_donations() );
		self::assertTrue( $result?->has_target() );
		self::assertSame( 123, $result?->get_target_amount() );
		self::assertSame( 'RUB', $result?->get_currency_code() );
		self::assertSame( 550, $result?->get_collected_amount() );
		self::assertSame( 2, $result?->get_donations_count() );
		self::assertSame( '2026-03-21 10:00:00', $result?->get_created_at()->format( 'Y-m-d H:i:s' ) );
		self::assertSame( '2026-03-22 11:15:00', $result?->get_updated_at()?->format( 'Y-m-d H:i:s' ) );
	}

	#[Test]
	public function find_by_id_returns_campaign_without_target_or_update_timestamp_when_persistence_row_has_nulls(): void {

		$id = EntityId::create( 7 );

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn(
				$this->campaign_row(
					id: 7,
					accepts_donations: true,
					target_amount: null,
					collected_amount: 0,
					donations_count: 0,
					updated_at: null,
				),
			);

		$result = $this->repository->find_by_id( $id );

		self::assertTrue( $result?->accepts_donations() );
		self::assertFalse( $result?->has_target() );
		self::assertNull( $result?->get_target_amount() );
		self::assertSame( 0, $result?->get_collected_amount() );
		self::assertSame( 0, $result?->get_donations_count() );
		self::assertNull( $result?->get_updated_at() );
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

		$this->expectException( CampaignReadException::class );
		$this->expectExceptionMessage(
			'Campaign ID must be a positive integer. Given: 019b6bcb-2f32-7461-838f-67a1479fbdbe.',
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

		$this->expectException( CampaignReadException::class );
		$this->expectExceptionMessage( 'Failed to retrieve campaign "7".' );

		$this->repository->find_by_id( $id );
	}

	#[Test]
	public function find_by_id_throws_when_row_cannot_be_mapped_to_campaign(): void {

		$id = EntityId::create( 7 );
		$row = $this->campaign_row( id: 7 );
		$row['created_at'] = 'bad-date';

		$this->db
			->shouldReceive( 'get_by_id' )
			->once()
			->with( self::TABLE_NAME, 7 )
			->andReturn( $row );

		$this->expectException( CampaignReadException::class );
		$this->expectExceptionMessage( 'Failed to map campaign row "7".' );

		$this->repository->find_by_id( $id );
	}

	/**
	 * Returns a campaign row.
	 *
	 * @return array<string, string|null>
	 */
	private function campaign_row(
		int $id = 7,
		string $title = 'Hello',
		bool $accepts_donations = false,
		string $currency_code = 'RUB',
		?int $target_amount = 123,
		int $collected_amount = 0,
		int $donations_count = 0,
		?string $updated_at = null,
	): array {

		return [
			'id' => (string) $id,
			'title' => $title,
			'accepts_donations' => $accepts_donations ? '1' : '0',
			'currency_code' => $currency_code,
			'target_amount' => $target_amount === null ? null : (string) $target_amount,
			'collected_amount' => (string) $collected_amount,
			'donations_count' => (string) $donations_count,
			'created_at' => '2026-03-21 10:00:00',
			'updated_at' => $updated_at,
		];
	}
}
