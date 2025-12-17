<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Bootstrap\Container;

/**
 * Provides a method for registering all container bindings.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface ContainerBindingsRegistrarInterface {

	/**
	 * Registers all bindings into the given container.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Receives the service bindings for resolution at runtime.
	 */
	public function register_bindings_into_container( ContainerInterface $container ): void;
}
