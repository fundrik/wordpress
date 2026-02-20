<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

/**
 * Registers all container bindings.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ContainerBindingsRegistrar {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerBindingsRegistry $registry Provides the list of container bindings declarations.
	 */
	public function __construct(
		private ContainerBindingsRegistry $registry,
	) {}

	/**
	 * Registers all bindings into the given container.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Receives the service bindings for resolution at runtime.
	 */
	public function register_bindings_into_container( ContainerInterface $container ): void {

		foreach ( $this->registry->get_singletons() as $abstract => $concrete ) {

			if ( is_int( $abstract ) ) {
				$abstract = $concrete;
			}

			$container->singleton( $abstract, $concrete );
		}

		foreach ( $this->registry->get_bindings() as $abstract => $concrete ) {

			$container->bind( $abstract, $concrete );
		}
	}
}
