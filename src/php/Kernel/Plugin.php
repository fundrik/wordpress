<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel;

use Fundrik\WordPress\Kernel\Ports\BootUnitRunnerPort;
use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;

/**
 * Bootstraps the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class Plugin {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MigrationRunnerPort $migration_runner Applies pending database migrations.
	 * @param HookDispatcherRegistrarPort $hook_dispatcher_registrar Registers all declared WordPress hook dispatchers.
	 * @param BootUnitRunnerPort $boot_unit_runner Boots all declared WordPress integration units.
	 */
	public function __construct(
		private MigrationRunnerPort $migration_runner,
		private HookDispatcherRegistrarPort $hook_dispatcher_registrar,
		private BootUnitRunnerPort $boot_unit_runner,
	) {}

	/**
	 * Runs the application.
	 *
	 * @since 1.0.0
	 */
	public function run(): void {

		$this->migration_runner->migrate();

		$this->run_wordpress();
	}

	/**
	 * Boots WordPress integration.
	 *
	 * @since 1.0.0
	 */
	private function run_wordpress(): void {

		$this->hook_dispatcher_registrar->register_all();

		$this->boot_unit_runner->boot_all();
	}
}
