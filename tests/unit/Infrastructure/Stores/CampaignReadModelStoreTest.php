<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Stores;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Stores\CampaignReadModelStore;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignReadModelStore::class )]
final class CampaignReadModelStoreTest extends MockeryTestCase {

	private DatabasePort&MockInterface $database;
	private CampaignReadModelStore $store;

	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->store = new CampaignReadModelStore( $this->database );
	}

	#[Test]
	public function it_applies_summary_deltas_to_campaign_row(): void {

		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->with(
				'fundrik_campaigns',
				77,
				[
					'collected_amount' => 500,
					'donations_count' => 1,
				],
			);

		$this->store->apply_summary_deltas( 77, 500, 1 );
	}

	#[Test]
	public function it_updates_summary_values_in_campaign_row(): void {

		$this->database
			->shouldReceive( 'update' )
			->once()
			->with(
				'fundrik_campaigns',
				[
					'collected_amount' => 2_500,
					'donations_count' => 12,
				],
				[
					'id' => 77,
				],
			);

		$this->store->update_summary( 77, 2_500, 12 );
	}
}
