<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminMenuActionHookDispatcher;
use Override;

/**
 * Registers all declared admin pages on the WordPress admin menu hook.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterAdminPagesBootUnit implements BootUnitInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminMenuActionHookDispatcher $admin_menu_hook Dispatches the WordPress 'admin_menu' action.
	 * @param AdminPageRegistrar $admin_page_registrar Registers configured admin pages.
	 * @param BootUnitLogger $logger Writes structured log entries.
	 */
	public function __construct(
		private AdminMenuActionHookDispatcher $admin_menu_hook,
		private AdminPageRegistrar $admin_page_registrar,
		private BootUnitLogger $logger,
	) {

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches the admin pages registration callback to the WordPress 'admin_menu' action.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->admin_menu_hook->attach( $this->admin_page_registrar->register_all( ... ) );
	}
}
