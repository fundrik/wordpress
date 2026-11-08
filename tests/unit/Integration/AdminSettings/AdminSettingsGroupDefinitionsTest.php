<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminSettingsGroupDefinitions::class )]
final class AdminSettingsGroupDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function classes_returns_settings_groups_in_registration_order(): void {

		self::assertSame(
			[
				GeneralSettingsGroup::class,
				CheckoutSettingsGroup::class,
				CampaignSettingsGroup::class,
				DonationFormSettingsGroup::class,
			],
			AdminSettingsGroupDefinitions::classes(),
		);
	}
}
