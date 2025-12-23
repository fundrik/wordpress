<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Bootstrap;

use Fundrik\WordPress\Bootstrap\Container\ContainerInterface;
use Fundrik\WordPress\Kernel\Plugin;
use Fundrik\WordPress\Kernel\Ports\EventListenerRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\HookBridgeRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;

/**
 * Builds the plugin runtime instance by resolving its runtime dependencies.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PluginFactory {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Resolves runtime dependencies required to start the plugin.
	 */
	public function __construct(
		private ContainerInterface $container,
	) {}

	/**
	 * Creates the plugin runtime instance with all required dependencies resolved.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin The plugin instance ready to be started.
	 */
	public function create(): Plugin {

		return new Plugin(
			$this->container->make( EventListenerRegistrarPort::class ),
			$this->container->make( MigrationRunnerPort::class ),
			$this->container->make( HookBridgeRegistrarPort::class ),
		);
	}
}
