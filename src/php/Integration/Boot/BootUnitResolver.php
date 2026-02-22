<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use Fundrik\WordPress\Kernel\Container\ContainerInterface;
use InvalidArgumentException;

/**
 * Resolves boot unit instances.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class BootUnitResolver {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Resolves boot unit instances through the container.
	 */
	public function __construct(
		private ContainerInterface $container,
	) {}

	/**
	 * Resolves a boot unit instance by class name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The boot unit class.
	 *
	 * @phpstan-param class-string<BootUnitInterface> $class_name
	 *
	 * @return BootUnitInterface The resolved boot unit.
	 *
	 * @throws InvalidArgumentException When the class does not exist or does not implement BootUnitInterface.
	 * @throws ContainerException When the container cannot resolve the boot unit.
	 */
	public function resolve( string $class_name ): BootUnitInterface {

		if ( ! class_exists( $class_name ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot resolve the boot unit: the class must exist. Given: %s.',
					$class_name,
				),
			);
		}

		if ( ! is_subclass_of( $class_name, BootUnitInterface::class ) ) {

			throw new InvalidArgumentException(
				sprintf(
					'Cannot resolve the boot unit: the class must implement %s. Given: %s.',
					BootUnitInterface::class,
					$class_name,
				),
			);
		}

		return $this->container->make( $class_name );
	}
}
