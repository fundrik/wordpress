<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use ReflectionClass;

/**
 * Extracts the migration version via the #[MigrationVersion] attribute.
 *
 * Ensures that a migration declares its version.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class MigrationVersionReader {

	/**
	 * Ensures that the version follows the sortable pattern `YYYY_MM_DD_XX`.
	 */
	private const string VERSION_REGEX = '/^\d{4}_\d{2}_\d{2}_\d{2}$/';

	/**
	 * Returns the version from a migration class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the migration.
	 *
	 * @phpstan-param class-string $class_name
	 *
	 * @return string The declared migration version.
	 *
	 * @throws MigrationException When the version is missing or invalid.
	 */
	public function get_version( string $class_name ): string {

		$attributes = ( new ReflectionClass( $class_name ) )->getAttributes( MigrationVersion::class );

		if ( $attributes === [] || count( $attributes ) !== 1 ) {

			throw new MigrationException(
				sprintf(
					'Cannot read migration version: the class "%s" must declare exactly one #[MigrationVersion].',
					$class_name,
				),
			);
		}

		$value = $attributes[0]->newInstance()->value;

		if ( preg_match( self::VERSION_REGEX, $value ) !== 1 ) {

			throw new MigrationException(
				sprintf(
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					'Cannot read migration version: the value must follow "YYYY_MM_DD_XX" for the class "%s". Given: %s.',
					$class_name,
					$value,
				),
			);
		}

		return $value;
	}
}
