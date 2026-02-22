<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

/**
 * Provides methods for bootstrapping WordPress integration units.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface BootUnitInterface {

	/**
	 * Bootstraps the integration unit.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void;
}
