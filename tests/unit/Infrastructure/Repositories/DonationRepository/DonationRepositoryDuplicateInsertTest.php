<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Repositories\DonationRepository;

use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationAlreadyExistsException;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationRepository;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseDuplicateKeyException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationRepository::class )]
#[CoversClass( DonationAlreadyExistsException::class )]
final class DonationRepositoryDuplicateInsertTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_donations';
	private const string DONATION_ID = '01956b66-c80b-4f0e-b8d4-4c4f9f7d5531';

	private DatabasePort&MockInterface $db;
	private DonationRepository $repository;

	protected function setUp(): void {

		parent::setUp();

		/**
		 * Test database mock.
		 *
		 * @var DatabasePort&MockInterface $db
		 */
		$db = Mockery::mock( DatabasePort::class );

		$this->db = $db;
		$this->repository = new DonationRepository( $this->db, new DonationFactory() );
	}

	#[Test]
	public function insert_throws_when_database_reports_duplicate_donation_id(): void {

		$donation = ( new DonationFactory() )->create_pending_from_primitives(
			id: self::DONATION_ID,
			campaign_id: 77,
			amount: 1_000,
			currency_code: 'USD',
		);

		$this->db
			->shouldReceive( 'insert' )
			->once()
			->with( self::TABLE_NAME, Mockery::type( 'array' ) )
			->andThrow( new FakeDatabaseDuplicateKeyException( 'Duplicate.' ) );

		$this->db->shouldNotReceive( 'get_by_id' );

		$this->expectException( DonationAlreadyExistsException::class );
		$this->expectExceptionMessage(
			sprintf( 'Cannot insert donation "%s": donation already exists.', self::DONATION_ID ),
		);

		$this->repository->insert( $donation );
	}
}
