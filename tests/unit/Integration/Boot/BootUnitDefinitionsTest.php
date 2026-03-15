<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\BootUnitDefinitions;
use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterRestApiRoutesBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\SyncPostToCampaignBootUnit;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( BootUnitDefinitions::class )]
final class BootUnitDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function it_exposes_expected_boot_unit_classes(): void {

		$this->assertSame(
			[
				FilterAllowedBlocksByPostTypeBootUnit::class,
				RegisterBlocksBootUnit::class,
				RegisterPostTypesBootUnit::class,
				RegisterRestApiRoutesBootUnit::class,
				SyncPostToCampaignBootUnit::class,
			],
			BootUnitDefinitions::classes(),
		);
	}
}
