<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\Core\Support\TypeCaster;
use Fundrik\WordPress\Infrastructure\Container\ContainerInterface;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\Helpers\LoggerFormatter;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Psr\Log\LoggerInterface;

/**
 * Applies versioned database migrations in order.
 *
 * @since 1.0.0
 *
 * @internal
 *
 * @todo Add a lock to prevent race conditions.
 */
final readonly class MigrationRunner implements MigrationRunnerInterface {

	private const OPTION_KEY = 'fundrik_db_version';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ContainerInterface $container Resolves migration class instances.
	 * @param DatabaseInterface $database Provides access to the WordPress database.
	 * @param StorageInterface $storage Stores and retrieves the current DB schema version.
	 * @param MigrationVersionReader $version_reader Extracts version information from migration classes.
	 * @param MigrationRegistry $registry Provides the list of migration classes and target DB version.
	 * @param LoggerInterface $logger Logs the migration steps for debugging and traceability.
	 */
	public function __construct(
		private ContainerInterface $container,
		private DatabaseInterface $database,
		private StorageInterface $storage,
		private MigrationVersionReader $version_reader,
		private MigrationRegistry $registry,
		private LoggerInterface $logger,
	) {}


	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies all pending migrations with versions newer than the last applied.
	 *
	 * @since 1.0.0
	 */
	public function migrate(): void {

		$current_db_version = $this->get_current_db_version();
		$target_db_version = $this->registry->get_target_db_version();

		$this->logger->debug(
			'Starting migration process.',
			[
				'current_version' => $current_db_version,
				'target_version' => $target_db_version,
			],
		);

		if ( ! $this->should_migrate( $current_db_version, $target_db_version ) ) {
			$this->logger->debug( 'No migrations needed. Database is already up to date.' );
			return;
		}

		$charset_collate = $this->database->get_charset_collate();

		$applied_count = 0;

		foreach ( $this->get_sorted_classes() as $class ) {
			$applied_count += $this->maybe_apply_migration( $class, $current_db_version, $charset_collate ) ? 1 : 0;
		}

		$final_version = $this->get_current_db_version();

		$this->logger->info(
			"Migration process completed: {$applied_count} applied, DB version now {$final_version}.",
			[
				'applied' => $applied_count,
				'from_version' => $current_db_version,
				'to_version' => $final_version,
				'target_version' => $target_db_version,
			],
		);
	}
	// phpcs:enable

	/**
	 * Determines whether a migration is required based on version comparison.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current The currently stored DB version.
	 * @param string $target The latest available DB version.
	 *
	 * @return bool Whether there are pending migrations to apply.
	 */
	private function should_migrate( string $current, string $target ): bool {

		return version_compare( $target, $current, '>' );
	}

	/**
	 * Applies a single migration if its version is newer than the current.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Resolves and applies the given migration class.
	 * @param string $current_db_version The version currently applied.
	 * @param string $charset_collate The charset and collation for the table schema.
	 *
	 * @phpstan-param class-string<AbstractMigration> $class_name
	 */
	private function maybe_apply_migration(
		string $class_name,
		string $current_db_version,
		string $charset_collate,
	): bool {

		$version = $this->version_reader->get_version( $class_name );
		$context = LoggerFormatter::migration_context( $class_name, $version );

		if ( version_compare( $version, $current_db_version, '<=' ) ) {
			$this->logger->debug( 'Skipping already applied migration.', $context );
			return false;
		}

		$this->logger->debug( 'Applying migration.', $context );

		$migration = $this->container->get( $class_name );
		$migration->apply( $charset_collate );

		if ( ! $this->update_current_db_version( $version ) ) {
			$this->logger->warning( 'Failed to update stored DB version after migration.', $context );
		}

		$this->logger->debug( 'Migration applied and version updated.', $context );
		return true;
	}

	/**
	 * Returns the list of migration class names sorted by version.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> The sorted list of migration classes.
	 *
	 * @phpstan-return array<class-string<AbstractMigration>>
	 */
	private function get_sorted_classes(): array {

		$classes = $this->registry->get_migration_classes();

		usort(
			$classes,
			fn ( string $a, string $b ) => version_compare(
				$this->version_reader->get_version( $a ),
				$this->version_reader->get_version( $b ),
			),
		);

		return $classes;
	}

	/**
	 * Returns the current database schema version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The stored DB version.
	 */
	private function get_current_db_version(): string {

		return TypeCaster::to_string( $this->storage->get( self::OPTION_KEY, '0000_00_00_00' ) );
	}

	/**
	 * Marks the given migration version as the current database schema version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version The version to store as current.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function update_current_db_version( string $version ): bool {

		return $this->storage->set( self::OPTION_KEY, $version );
	}
}
