<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;

/**
 * Creates and initializes the dependency injection container.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ContainerFactory {

	/**
	 * Creates the container instance prepared for further bindings.
	 *
	 * @since 1.0.0
	 *
	 * @return ContainerInterface The initialized container instance.
	 */
	public function create(): ContainerInterface {

		$laravel_container = new LaravelContainer();
		$container = new Container( $laravel_container );

		$container->instance( ContainerInterface::class, $container );
		$container->instance( LaravelContainerInterface::class, $laravel_container );

		return $container;
	}
}
