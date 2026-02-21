<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;

/**
 * Registers all WordPress hook dispatchers.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class HookDispatcherRegistrar implements HookDispatcherRegistrarPort {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param HookDispatcherRegistry $registry Provides the list of hook dispatcher classes.
	 * @param HookDispatcherFactory $factory Creates hook dispatcher instances.
	 */
	public function __construct(
		private HookDispatcherRegistry $registry,
		private HookDispatcherFactory $factory,
	) {}

	/**
	 * Registers all declared hooks.
	 *
	 * @since 1.0.0
	 *
	 * @throws RuntimeException When the hook class does not implement the required interface.
	 */
	public function register_all(): void {

		foreach ( $this->registry->get_dispatcher_classes() as $class_name ) {

			$dispatcher = $this->factory->create( $class_name );
			$dispatcher->register();
		}
	}
}
