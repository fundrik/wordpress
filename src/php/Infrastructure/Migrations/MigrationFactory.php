<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\DatabaseInterface;

/**
 * Creates migration instances.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class MigrationFactory {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabaseInterface $database Executes SQL queries during the migration.
	 */
	public function __construct(
		private DatabaseInterface $database,
	) {}

	/**
	 * Creates the migration by the given class name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 *
	 * @return AbstractMigration The migration instance.
	 *
	 * @throws MigrationException When the class does not exist or does not extend AbstractMigration.
	 */
	public function create( string $class_name ): AbstractMigration {

		if ( ! class_exists( $class_name ) ) {

			throw new MigrationException(
				sprintf( 'Cannot create the migration: the class must exist. Given: %s.', $class_name ),
			);
		}

		if ( ! is_subclass_of( $class_name, AbstractMigration::class ) ) {

			throw new MigrationException(
				sprintf(
					'Cannot create the migration: the class must extend %s. Given: %s.',
					AbstractMigration::class,
					$class_name,
				),
			);
		}

		return new $class_name( $this->database );
	}
}
