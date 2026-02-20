<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel;

use Fundrik\WordPress\Kernel\Ports\HookBridgeRegistrarPort;
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
	 * @param HookBridgeRegistrarPort $hook_bridge_registrar Registers WordPress hook-to-event bridges.
	 */
	public function __construct(
		private MigrationRunnerPort $migration_runner,
		private HookBridgeRegistrarPort $hook_bridge_registrar,
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
	 * Boots WordPress-specific infrastructure.
	 *
	 * @since 1.0.0
	 */
	private function run_wordpress(): void {

		$this->hook_bridge_registrar->register_all();
	}
}
