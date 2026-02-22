<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use Fundrik\WordPress\Kernel\Container\ContainerInterface;
use InvalidArgumentException;

/**
 * Resolves hook dispatcher instances.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class HookDispatcherResolver {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Resolves hook dispatcher instances through the container.
	 */
	public function __construct(
		private ContainerInterface $container,
	) {}

	/**
	 * Resolves a hook dispatcher instance by class name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The hook dispatcher class.
	 *
	 * @phpstan-param class-string<HookDispatcherInterface> $class_name
	 *
	 * @return HookDispatcherInterface The resolved hook dispatcher.
	 *
	 * @throws InvalidArgumentException When the class does not exist or does not implement HookDispatcherInterface.
	 * @throws ContainerException When the container cannot resolve the hook dispatcher.
	 */
	public function resolve( string $class_name ): HookDispatcherInterface {

		if ( ! class_exists( $class_name ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot resolve the hook dispatcher: the class must exist. Given: %s.',
					$class_name,
				),
			);
		}

		if ( ! is_subclass_of( $class_name, HookDispatcherInterface::class ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot resolve the hook dispatcher: the class must implement %s. Given: %s.',
					HookDispatcherInterface::class,
					$class_name,
				),
			);
		}

		return $this->container->make( $class_name );
	}
}
