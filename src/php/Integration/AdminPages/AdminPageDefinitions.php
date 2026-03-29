<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages;

use Fundrik\WordPress\Integration\AdminPages\Pages\SettingsAdminPage;

/**
 * Provides admin page declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminPageDefinitions {

	public const string ROOT_MENU_SLUG = 'fundrik';

	/**
	 * Returns the configured admin page classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<AdminPageInterface>>
	 */
	public static function classes(): array {

		return [
			SettingsAdminPage::class,
		];
	}
}
