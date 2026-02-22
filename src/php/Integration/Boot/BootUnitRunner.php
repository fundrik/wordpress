<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use Fundrik\WordPress\Kernel\Ports\BootUnitRunnerPort;

/**
 * Registers all WordPress integration boot units.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class BootUnitRunner implements BootUnitRunnerPort {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param BootUnitRegistry $registry Provides the list of boot unit classes.
	 * @param BootUnitResolver $resolver Resolves boot unit instances.
	 */
	public function __construct(
		private BootUnitRegistry $registry,
		private BootUnitResolver $resolver,
	) {}

	/**
	 * Boot all declared boot units.
	 *
	 * @since 1.0.0
	 *
	 * @throws RuntimeException When the boot unit class does not implement the required interface.
	 */
	public function boot_all(): void {

		foreach ( $this->registry->get_boot_unit_classes() as $class_name ) {

			$dispatcher = $this->resolver->resolve( $class_name );
			$dispatcher->boot();
		}
	}
}
