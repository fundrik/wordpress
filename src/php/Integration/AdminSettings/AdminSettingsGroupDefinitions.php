<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;

/**
 * Provides admin settings group declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminSettingsGroupDefinitions {

	/**
	 * Returns the configured admin settings group classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<AdminSettingsGroupInterface>>
	 */
	public static function classes(): array {

		return [
			GeneralSettingsGroup::class,
			CampaignSettingsGroup::class,
			DonationFormSettingsGroup::class,
		];
	}
}
