<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettings;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettings;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminSettingsDefinitions::class )]
final class AdminSettingsDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function it_exposes_expected_admin_settings_classes(): void {

		$this->assertSame(
			[
				GeneralSettings::class,
				DonationFormSettings::class,
			],
			AdminSettingsDefinitions::classes(),
		);
	}
}
