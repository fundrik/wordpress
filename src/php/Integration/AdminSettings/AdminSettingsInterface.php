<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

/**
 * Represents a WordPress admin settings registration.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface AdminSettingsInterface {

	/**
	 * Registers the admin settings in WordPress.
	 *
	 * @since 1.0.0
	 */
	public function register(): void;
}
