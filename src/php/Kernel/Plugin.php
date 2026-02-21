<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel;

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
	 * @param MigrationRunnerPort $migration_runner Applies database schema migrations.
	 * @param HookDispatcherRegistrarPort $hook_dispatcher_registrar Registers WordPress hook dispatchers.
	 */
	public function __construct(
		private MigrationRunnerPort $migration_runner,
		private HookDispatcherRegistrarPort $hook_dispatcher_registrar,
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
	}
}
