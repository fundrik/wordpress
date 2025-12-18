<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel;

use Fundrik\WordPress\Kernel\Ports\Out\EventListenerRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\Out\HookBridgeRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\Out\MigrationRunnerPort;

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
	 * @param EventListenerRegistrarPort $event_listener_registrar Registers application event listeners.
	 * @param MigrationRunnerPort $migration_runner Applies database schema migrations.
	 * @param HookBridgeRegistrarPort $hook_bridge_registrar Registers WordPress hook-to-event bridges.
	 */
	public function __construct(
		private EventListenerRegistrarPort $event_listener_registrar,
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

		$this->event_listener_registrar->register_all();

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
