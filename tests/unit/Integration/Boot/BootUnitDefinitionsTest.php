<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\BootUnitDefinitions;
use Fundrik\WordPress\Integration\Boot\Units\ExposeCampaignEditorSettingsBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\InitializeFundrikAdminBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\LogCreateDonationRestRequestFailuresBootUnit;
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
	public function classes_returns_boot_units_in_registration_order(): void {

		self::assertSame(
			[
				ExposeCampaignEditorSettingsBootUnit::class,
				FilterAllowedBlocksByPostTypeBootUnit::class,
				InitializeFundrikAdminBootUnit::class,
				LogCreateDonationRestRequestFailuresBootUnit::class,
				RegisterBlocksBootUnit::class,
				RegisterPostTypesBootUnit::class,
				RegisterRestApiRoutesBootUnit::class,
				SyncPostToCampaignBootUnit::class,
			],
			BootUnitDefinitions::classes(),
		);
	}
}
