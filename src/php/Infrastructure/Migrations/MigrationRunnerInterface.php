<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

/**
 * Provides methods for running database migrations.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface MigrationRunnerInterface {

	/**
	 * Applies all pending migrations in ascending version order.
	 *
	 * @since 1.0.0
	 *
	 * @throws MigrationException When any migration step fails.
	 */
	public function migrate(): void;
}
