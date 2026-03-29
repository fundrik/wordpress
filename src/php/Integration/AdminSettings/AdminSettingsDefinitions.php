<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettings;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettings;

/**
 * Provides admin settings declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminSettingsDefinitions {

	public const string OPTION_GROUP = 'fundrik_settings';

	/**
	 * Returns the configured admin settings classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<AdminSettingsInterface>>
	 */
	public static function classes(): array {

		return [
			GeneralSettings::class,
			DonationFormSettings::class,
		];
	}
}
