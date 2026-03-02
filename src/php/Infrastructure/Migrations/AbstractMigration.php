<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\DatabasePort;

/**
 * Defines a base class for applying database migrations.
 *
 * @since 1.0.0
 *
 * @internal
 */
abstract readonly class AbstractMigration {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabasePort $database Executes SQL queries during the migration.
	 */
	public function __construct(
		protected DatabasePort $database,
	) {}

	/**
	 * Applies the schema changes defined by the migration.
	 *
	 * @since 1.0.0
	 *
	 * @param string $charset_collate The charset and collation string for table creation.
	 *
	 * @throws MigrationException When the migration cannot be applied.
	 */
	abstract public function apply( string $charset_collate ): void;
}
