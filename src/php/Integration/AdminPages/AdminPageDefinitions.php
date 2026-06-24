<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages;

use Fundrik\WordPress\Integration\AdminPages\Pages\DonationsAdminPage;
use Fundrik\WordPress\Integration\AdminPages\Pages\RootAdminPage;
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
	public const string DONATIONS_PAGE_ID = 'fundrik_donations';
	public const string SETTINGS_PAGE_ID = 'fundrik_settings';
	public const string CONTENT_CAPABILITY = 'edit_posts';
	public const string SETTINGS_CAPABILITY = 'manage_options';

	/**
	 * Returns the configured admin page classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<AdminPageInterface>>
	 */
	public static function classes(): array {

		return [
			RootAdminPage::class,
			DonationsAdminPage::class,
			SettingsAdminPage::class,
		];
	}
}
