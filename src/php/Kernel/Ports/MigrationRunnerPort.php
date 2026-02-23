<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports;

use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;

/**
 * Provides methods for running database migrations.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface MigrationRunnerPort {

	/**
	 * Applies all pending migrations in ascending version order.
	 *
	 * @since 1.0.0
	 *
	 * @throws MigrationException When any migration step fails.
	 */
	public function migrate(): void;
}
