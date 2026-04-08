<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminSettingsGroupDefinitions::class )]
final class AdminSettingsGroupDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function it_exposes_expected_admin_settings_group_classes(): void {

		$this->assertSame(
			[
				GeneralSettingsGroup::class,
				CampaignSettingsGroup::class,
				DonationFormSettingsGroup::class,
			],
			AdminSettingsGroupDefinitions::classes(),
		);
	}
}
