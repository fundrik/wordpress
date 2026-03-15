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
	 * Ensures that the version follows the sortable pattern `YYYY_MM_DD_XX`.
	 */
	private const string VERSION_REGEX = '/^\d{4}_\d{2}_\d{2}_\d{2}$/';

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
	 * Returns the validated migration version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The sortable migration version.
	 *
	 * @throws MigrationException When the version is invalid.
	 */
	final public static function version(): string {

		$value = static::define_version();

		if ( preg_match( self::VERSION_REGEX, $value ) !== 1 ) {

			throw new MigrationException(
				sprintf(
					'Migration version must follow "YYYY_MM_DD_XX". Given: %s.',
					$value,
				),
			);
		}

		return $value;
	}

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

	/**
	 * Defines the raw migration version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The raw migration version.
	 */
	abstract protected static function define_version(): string;
}
