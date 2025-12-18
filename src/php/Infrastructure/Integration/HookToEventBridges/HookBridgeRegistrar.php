<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges;

use Fundrik\WordPress\Bootstrap\Container\ContainerInterface;
use Fundrik\WordPress\Kernel\Ports\Out\HookBridgeRegistrarPort;

/**
 * Registers all WordPress hook-to-event bridges.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class HookBridgeRegistrar implements HookBridgeRegistrarPort {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param HookBridgeRegistry $registry Provides the list of bridge classes.
	 * @param ContainerInterface $container Resolves bridge class instances.
	 */
	public function __construct(
		private HookBridgeRegistry $registry,
		private ContainerInterface $container,
	) {}

	/**
	 * Registers all declared hook-to-event bridges.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void {

		foreach ( $this->registry->get_bridge_classes() as $class ) {
			$this->container->make( $class )->register();
		}
	}
}
