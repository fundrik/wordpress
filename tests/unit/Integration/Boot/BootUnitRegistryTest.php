<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\BootUnitRegistry;
use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\SyncPostToCampaignBootUnit;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( BootUnitRegistry::class )]
final class BootUnitRegistryTest extends FundrikTestCase {

	#[Test]
	public function it_returns_expected_boot_unit_class_names(): void {

		$registry = new BootUnitRegistry();

		self::assertSame(
			[
				FilterAllowedBlocksByPostTypeBootUnit::class,
				RegisterBlocksBootUnit::class,
				RegisterPostTypesBootUnit::class,
				SyncPostToCampaignBootUnit::class,
			],
			$registry->get_boot_unit_classes(),
		);
	}
}
