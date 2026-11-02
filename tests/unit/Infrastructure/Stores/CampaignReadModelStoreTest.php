<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Stores;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Stores\CampaignReadModelStore;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignReadModelStore::class )]
final class CampaignReadModelStoreTest extends MockeryTestCase {

	private const string TABLE_NAME = 'fundrik_campaigns';

	private DatabasePort&MockInterface $database;
	private CampaignReadModelStore $store;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->store = new CampaignReadModelStore( $this->database );
	}

	#[Test]
	public function apply_summary_deltas_passes_deltas_to_database(): void {

		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->with(
				self::TABLE_NAME,
				77,
				[
					'collected_amount' => -500,
					'donations_count' => -1,
				],
			);

		$this->store->apply_summary_deltas( 77, -500, -1 );
	}

	#[Test]
	public function update_summary_passes_summary_values_to_database(): void {

		$this->database
			->shouldReceive( 'update' )
			->once()
			->with(
				self::TABLE_NAME,
				[
					'collected_amount' => 2_500,
					'donations_count' => 12,
				],
				[
					'id' => 77,
				],
			)
			->andReturn( 1 );

		$this->store->update_summary( 77, 2_500, 12 );
	}
}
