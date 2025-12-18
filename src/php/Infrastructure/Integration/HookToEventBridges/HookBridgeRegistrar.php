<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges;

use Fundrik\WordPress\Bootstrap\Container\ContainerInterface;
use Fundrik\WordPress\Kernel\Ports\Out\HookBridgeRegistrarPort;
use RuntimeException;

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
	 *
	 * @throws RuntimeException Thrown when the hook bridge class does not implement the required interface.
	 */
	public function register_all(): void {

		foreach ( $this->registry->get_bridge_classes() as $class ) {

			if ( ! is_subclass_of( $class, HookToEventBridgeInterface::class ) ) {

				throw new RuntimeException(
					sprintf(
						'Hook bridge must implement %s. Given: %s.',
						HookToEventBridgeInterface::class,
						$class,
					),
				);
			}

			$this->container->make( $class )->register();
		}
	}
}
