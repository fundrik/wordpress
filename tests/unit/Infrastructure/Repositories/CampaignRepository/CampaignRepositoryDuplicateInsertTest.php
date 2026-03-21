<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignAlreadyExistsException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository\CampaignRepository;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseDuplicateKeyException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignRepository::class )]
#[CoversClass( CampaignAlreadyExistsException::class )]
final class CampaignRepositoryDuplicateInsertTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_campaigns';

	private DatabasePort&MockInterface $db;
	private CampaignRepository $repository;

	protected function setUp(): void {

		parent::setUp();

		/**
		 * Test database mock.
		 *
		 * @var DatabasePort&MockInterface $db
		 */
		$db = Mockery::mock( DatabasePort::class );

		$this->db = $db;
		$this->repository = new CampaignRepository( $this->db, new CampaignFactory() );
	}

	#[Test]
	public function insert_throws_when_database_reports_duplicate_campaign_id(): void {

		$campaign = ( new CampaignFactory() )->create_from_primitives(
			id: 7,
			version: 1,
			title: 'Hello',
			accepts_donations: true,
			currency_code: 'USD',
			target_amount: null,
		);

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) )
			->andThrow( new FakeDatabaseDuplicateKeyException( 'Duplicate.' ) );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( CampaignAlreadyExistsException::class );
		$this->expectExceptionMessage( 'Cannot insert campaign "7": campaign already exists.' );

		$this->repository->insert( $campaign );
	}
}

