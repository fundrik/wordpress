<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\Core\Support\TypeCaster;
use Fundrik\WordPress\Infrastructure\Container\ContainerInterface;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
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

	/**
	 * Applies all pending migrations with versions newer than the last applied.
	 *
	 * @since 1.0.0
	 */
	public function migrate(): void {

		$current_db_version = $this->get_current_db_version();
		$target_db_version = $this->registry->get_target_db_version();

		$this->log_migrations_start( $current_db_version, $target_db_version );

		if ( ! $this->should_migrate( $current_db_version, $target_db_version ) ) {
			$this->log_no_migrations_needed();
			return;
		}

		$charset_collate = $this->database->get_charset_collate();

		$applied_count = 0;

		foreach ( $this->get_sorted_classes() as $class ) {
			$applied_count += $this->maybe_apply_migration( $class, $current_db_version, $charset_collate ) ? 1 : 0;
		}

		$final_version = $this->get_current_db_version();

		$this->log_migrations_completed(
			applied: $applied_count,
			from: $current_db_version,
			to: $final_version,
			target: $target_db_version,
		);
	}

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

		if ( version_compare( $version, $current_db_version, '<=' ) ) {
			$this->log_migration_skipped( $class_name, $version );
			return false;
		}

		$this->log_migration_applying( $class_name, $version );

		$migration = $this->container->get( $class_name );
		$migration->apply( $charset_collate );

		if ( ! $this->update_current_db_version( $version ) ) {
			$this->log_db_version_update_failed( $class_name, $version );
			$this->log_migration_applied( $class_name, $version, version_updated: false );
			return true;
		}

		$this->log_migration_applied( $class_name, $version, version_updated: true );

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

	/**
	 * Logs the beginning of the migration process.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current The current database version.
	 * @param string $target The target database version.
	 */
	private function log_migrations_start( string $current, string $target ): void {

		$this->logger->debug(
			'Starting migration process.',
			[
				'system' => 'db_migration',
				'current_version' => $current,
				'target_version' => $target,
			],
		);
	}

	/**
	 * Logs that no migrations are required because the database is already up to date.
	 *
	 * @since 1.0.0
	 */
	private function log_no_migrations_needed(): void {

		$this->logger->debug(
			'No migrations needed. Database is already up to date.',
			[
				'system' => 'db_migration',
				'outcome' => 'skipped',
			],
		);
	}

	/**
	 * Logs that the given migration has been skipped because it was already applied.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version string.
	 */
	private function log_migration_skipped( string $class_name, string $version ): void {

		$this->logger->debug(
			'Skipping already applied migration.',
			$this->logger_context( $class_name, $version, [ 'outcome' => 'skipped' ] ),
		);
	}

	/**
	 * Logs that the given migration is about to be applied.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version string.
	 */
	private function log_migration_applying( string $class_name, string $version ): void {

		$this->logger->debug(
			'Applying migration.',
			$this->logger_context( $class_name, $version ),
		);
	}

	/**
	 * Logs a failure to update the stored database version after applying a migration.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version string.
	 */
	private function log_db_version_update_failed( string $class_name, string $version ): void {

		$this->logger->warning(
			'Failed to update stored DB version after migration.',
			$this->logger_context( $class_name, $version ),
		);
	}

	/**
	 * Logs that the given migration has been applied, with an indicator
	 * whether the stored DB version was successfully updated.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version string.
	 * @param bool $version_updated Whether the DB version was successfully updated.
	 */
	private function log_migration_applied( string $class_name, string $version, bool $version_updated ): void {

		$this->logger->debug(
			$version_updated
			? 'Migration applied and version updated.'
			: 'Migration applied but version update failed.',
			$this->logger_context(
				$class_name,
				$version,
				[
					'outcome' => 'applied',
					'version_updated' => $version_updated,
				],
			),
		);
	}

	/**
	 * Logs the completion of the migration process with applied count and version details.
	 *
	 * @since 1.0.0
	 *
	 * @param int $applied The number of migrations applied.
	 * @param string $from The database version before migration.
	 * @param string $to The database version after migration.
	 * @param string $target The target database version.
	 */
	private function log_migrations_completed( int $applied, string $from, string $to, string $target ): void {

		$this->logger->info(
			"Migration process completed: {$applied} applied, DB version now {$to}.",
			[
				'system' => 'db_migration',
				'outcome' => $applied ? 'applied' : 'skipped',
				'applied' => $applied,
				'from_version' => $from,
				'to_version' => $to,
				'target_version' => $target,
			],
		);
	}

	/**
	 * Builds the structured logger context for a migration entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version string.
	 * @param array<string,mixed> $extra Additional context entries.
	 *
	 * @return array<string,mixed> The structured logging context for the migration.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( string $class_name, string $version, array $extra = [] ): array {

		return [
			'system' => 'db_migration',
			'migration_class' => $class_name,
			'migration_version' => $version,
		] + $extra;
	}
}
