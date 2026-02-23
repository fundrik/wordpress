<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\DatabaseException;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;

/**
 * Runs versioned database migrations.
 *
 * @since 1.0.0
 *
 * @internal
 *
 * @todo Add a lock to prevent race conditions.
 */
final readonly class MigrationRunner implements MigrationRunnerPort {

	private const string OPTION_KEY = 'fundrik_db_version';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabaseInterface $database Provides access to the database.
	 * @param StorageInterface $storage Stores and retrieves the current schema version.
	 * @param MigrationVersionReader $version_reader Reads migration versions from classes.
	 * @param MigrationRegistry $registry Provides the list of available migrations and the target version.
	 * @param MigrationRunnerLogger $logger Logs the migration steps for debugging and traceability.
	 * @param MigrationFactory $migration_factory Creates migration instances.
	 */
	public function __construct(
		private DatabaseInterface $database,
		private StorageInterface $storage,
		private MigrationVersionReader $version_reader,
		private MigrationRegistry $registry,
		private MigrationRunnerLogger $logger,
		private MigrationFactory $migration_factory,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies all pending migrations.
	 *
	 * @since 1.0.0
	 *
	 * @throws MigrationException When running migrations fails.
	 */
	public function migrate(): void {

		$from_db_version = $this->get_current_db_version();
		$target_db_version = $this->registry->get_target_db_version();

		if ( ! $this->should_migrate( $from_db_version, $target_db_version ) ) {
			return;
		}

		$charset_collate = $this->get_charset_collate();

		$applied_count = 0;
		$current_db_version = $from_db_version;

		foreach ( $this->get_sorted_migrations() as $version => $class ) {

			$applied = $this->maybe_apply_migration( $class, $version, $current_db_version, $charset_collate );

			if ( ! $applied ) {
				continue;
			}

			++$applied_count;
			$current_db_version = $version;
		}

		$this->logger->log_migrations_completed(
			applied: $applied_count,
			from: $from_db_version,
			to: $current_db_version,
			target: $target_db_version,
		);
	}
	// phpcs:enable

	/**
	 * Checks whether the target version is newer than the current version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current The current DB version.
	 * @param string $target The target DB version.
	 *
	 * @return bool Whether migrations need to run.
	 */
	private function should_migrate( string $current, string $target ): bool {

		return version_compare( $target, $current, '>' );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies the given migration when its version is newer than the current version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name Resolves and runs the migration class.
	 * @param string $version The migration version.
	 * @param string $current_db_version The currently stored DB version.
	 * @param string $charset_collate The charset and collation string for schema operations.
	 *
	 * @phpstan-param class-string<AbstractMigration> $class_name
	 *
	 * @return bool True when the migration was applied, false when skipped.
	 *
	 * @throws MigrationException When applying the migration or updating the stored DB version fails.
	 */
	private function maybe_apply_migration(
		string $class_name,
		string $version,
		string $current_db_version,
		string $charset_collate,
	): bool {

		if ( version_compare( $version, $current_db_version, '<=' ) ) {
			return false;
		}

		try {
			$migration = $this->migration_factory->create( $class_name );
			$migration->apply( $charset_collate );
		} catch ( MigrationException $e ) {
			$this->logger->log_migration_failed( $class_name, $version, $e );
			throw $e;
		}

		if ( ! $this->update_current_db_version( $version ) ) {
			$this->logger->log_db_version_update_failed( $class_name, $version );
			throw new MigrationException(
				sprintf(
					'Cannot complete migration: the stored DB version must be updated after applying. Given: %s.',
					$version,
				),
			);
		}

		$this->logger->log_migration_applied( $class_name, $version );

		return true;
	}
	// phpcs:enable

	/**
	 * Builds the migrations map sorted by version.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> The [version => class] map.
	 *
	 * @phpstan-return array<string, class-string<AbstractMigration>>
	 */
	private function get_sorted_migrations(): array {

		$map = [];

		foreach ( $this->registry->get_migration_classes() as $class ) {

			$version = $this->version_reader->get_version( $class );

			if ( isset( $map[ $version ] ) ) {

				throw new MigrationException(
					sprintf( 'Migration version must be unique. Given: %s.', $version ),
				);
			}

			$map[ $version ] = $class;
		}

		ksort( $map );

		return $map;
	}

	/**
	 * Reads the current schema version from storage.
	 *
	 * @since 1.0.0
	 *
	 * @return string The stored DB version.
	 */
	private function get_current_db_version(): string {

		return TypeCaster::to_string( $this->storage->get( self::OPTION_KEY, '0000_00_00_00' ) );
	}

	/**
	 * Returns the database charset and collation string for schema operations.
	 *
	 * @since 1.0.0
	 *
	 * @return string The charset and collation string.
	 *
	 * @throws MigrationException When determining the charset/collation fails.
	 */
	private function get_charset_collate(): string {

		try {
			return $this->database->get_charset_collate();
		} catch ( DatabaseException $e ) {
			$this->logger->log_charset_collate_failed( $e );
			throw new MigrationException( 'Cannot determine database charset and collation.', previous: $e );
		}
	}

	/**
	 * Stores the given schema version as the current DB version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Provides the version to store.
	 *
	 * @return bool Whether the version was stored.
	 */
	private function update_current_db_version( string $version ): bool {

		return $this->storage->set( self::OPTION_KEY, $version );
	}
}
