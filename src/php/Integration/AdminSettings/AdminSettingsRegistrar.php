<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

/**
 * Registers all configured admin settings in WordPress.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminSettingsRegistrar {

	/**
	 * The configured admin settings.
	 *
	 * @var list<AdminSettingsInterface>
	 */
	private array $admin_settings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsInterface ...$admin_settings Admin settings to register.
	 */
	public function __construct( AdminSettingsInterface ...$admin_settings ) {

		$this->admin_settings = $admin_settings;
	}

	/**
	 * Registers all configured admin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void {

		foreach ( $this->admin_settings as $admin_settings ) {
			$admin_settings->register();
		}
	}

	/**
	 * Returns the number of configured admin settings groups.
	 *
	 * @since 1.0.0
	 *
	 * @return int Admin settings count.
	 */
	public function count(): int {

		return count( $this->admin_settings );
	}
}
