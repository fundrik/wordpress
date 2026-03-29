<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsRegistrar;
use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminMenuActionHookDispatcher;
use Override;
use Throwable;

/**
 * Initializes the Fundrik admin area by registering pages and settings.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class InitializeFundrikAdminBootUnit implements BootUnitInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminMenuActionHookDispatcher $admin_menu_hook Dispatches the WordPress 'admin_menu' action.
	 * @param AdminInitActionHookDispatcher $admin_init_hook Dispatches the WordPress 'admin_init' action.
	 * @param AdminPageRegistrar $admin_page_registrar Registers configured admin pages.
	 * @param AdminSettingsRegistrar $admin_settings_registrar Registers configured admin settings.
	 * @param BootUnitLogger $logger Writes structured log entries.
	 */
	public function __construct(
		private AdminMenuActionHookDispatcher $admin_menu_hook,
		private AdminInitActionHookDispatcher $admin_init_hook,
		private AdminPageRegistrar $admin_page_registrar,
		private AdminSettingsRegistrar $admin_settings_registrar,
		private BootUnitLogger $logger,
	) {

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches the Fundrik admin initialization callbacks to WordPress admin hooks.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->admin_menu_hook->attach( $this->register_admin_pages( ... ) );
		$this->admin_init_hook->attach( $this->register_admin_settings( ... ) );
	}

	/**
	 * Registers all configured admin pages in WordPress.
	 *
	 * @since 1.0.0
	 */
	private function register_admin_pages(): void {

		try {
			$this->admin_page_registrar->register_all();
		} catch ( Throwable $e ) {

			$this->logger->log_error(
				'Admin page registration failed.',
				[
					'total_count' => $this->admin_page_registrar->count(),
					'exception' => $e,
				],
			);

			throw $e;
		}
	}

	/**
	 * Registers all configured admin settings in WordPress.
	 *
	 * @since 1.0.0
	 */
	private function register_admin_settings(): void {

		try {
			$this->admin_settings_registrar->register_all();
		} catch ( Throwable $e ) {

			$this->logger->log_error(
				'Admin settings registration failed.',
				[
					'total_count' => $this->admin_settings_registrar->count(),
					'exception' => $e,
				],
			);

			throw $e;
		}
	}
}
