<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports;

/**
 * Provides methods for running all boot units.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface BootUnitRunnerPort {

	/**
	 * Boots all declared boot units.
	 *
	 * @since 1.0.0
	 */
	public function boot_all(): void;
}
