<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StorageExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StorageNotFoundExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use InvalidArgumentException;
use Override;

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

	private const string INITIAL_DB_VERSION = '0000_00_00_00';
	private const string OPTION_KEY = 'fundrik_db_version';
	private const array PLUGIN_TABLES = [
		'fundrik_campaigns',
		'fundrik_donations',
	];

	/**
	 * The configured migrations.
	 *
	 * @var list<AbstractMigration>
	 */
	private array $migrations;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabasePort $database Provides access to the database.
	 * @param StoragePort $storage Stores and retrieves the current schema version.
	 * @param MigrationRunnerLogger $logger Logs the migration steps for debugging and traceability.
	 * @param AbstractMigration ...$migrations The configured migrations to apply if needed.
	 */
	public function __construct(
		private DatabasePort $database,
		private StoragePort $storage,
		private MigrationRunnerLogger $logger,
		AbstractMigration ...$migrations,
	) {

		$this->migrations = $migrations;
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies all pending migrations.
	 *
	 * @since 1.0.0
	 *
	 * @throws MigrationException When running migrations fails.
	 */
	#[Override]
	public function migrate(): void {

		$from_db_version = $this->resolve_starting_db_version();
		$target_db_version = $this->get_target_db_version();

		if ( ! $this->should_migrate( $from_db_version, $target_db_version ) ) {
			return;
		}

		$charset_collate = $this->get_charset_collate();
		$migrations = $this->get_sorted_migrations();

		$applied_count = 0;
		$current_db_version = $from_db_version;

		foreach ( $migrations as $version => $migration ) {

			$applied = $this->maybe_apply_migration( $migration, $version, $current_db_version, $charset_collate );

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
	 * Returns the latest configured migration version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The target DB version derived from configured migrations.
	 *
	 * @throws MigrationException When a migration version is invalid.
	 */
	private function get_target_db_version(): string {

		$target_db_version = self::INITIAL_DB_VERSION;

		foreach ( $this->migrations as $migration ) {

			$version = $migration::version();

			if ( ! version_compare( $version, $target_db_version, '>' ) ) {
				continue;
			}

			$target_db_version = $version;
		}

		return $target_db_version;
	}

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
	 * @param AbstractMigration $migration The migration instance to run.
	 * @param string $version The migration version.
	 * @param string $current_db_version The currently stored DB version.
	 * @param string $charset_collate The charset and collation string for schema operations.
	 *
	 * @return bool True when the migration was applied, false when skipped.
	 *
	 * @throws MigrationException When applying the migration or updating the stored DB version fails.
	 */
	private function maybe_apply_migration(
		AbstractMigration $migration,
		string $version,
		string $current_db_version,
		string $charset_collate,
	): bool {

		if ( version_compare( $version, $current_db_version, '<=' ) ) {
			return false;
		}

		try {
			$migration->apply( $charset_collate );
		} catch ( MigrationException $e ) {
			$this->logger->log_migration_failed( $migration::class, $version, $e );
			throw $e;
		}

		try {
			$this->update_current_db_version( $version );
		} catch ( StorageExceptionInterface $e ) {
			$this->logger->log_db_version_update_failed( $migration::class, $version );
			throw new MigrationException(
				sprintf(
					'Migration "%s" was applied, but updating stored DB version failed.',
					$version,
				),
				previous: $e,
			);
		}

		$this->logger->log_migration_applied( $migration::class, $version );

		return true;
	}
	// phpcs:enable

	/**
	 * Builds the migrations map sorted by version.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, AbstractMigration> The [version => migration] map.
	 */
	private function get_sorted_migrations(): array {

		$map = [];

		foreach ( $this->migrations as $migration ) {

			$version = $migration::version();

			if ( isset( $map[ $version ] ) ) {

				throw new MigrationException(
					sprintf( 'Migration version must be unique. Given: %s.', $version ),
				);
			}

			$map[ $version ] = $migration;
		}

		ksort( $map );

		return $map;
	}

	/**
	 * Resolves the starting schema version for migration decisions.
	 *
	 * @since 1.0.0
	 *
	 * @return string Starting DB version.
	 *
	 * @throws MigrationException When resolving the starting DB version fails.
	 */
	private function resolve_starting_db_version(): string {

		try {
			return TypeCaster::to_string( $this->storage->get( self::OPTION_KEY ) );
		} catch ( StorageNotFoundExceptionInterface $e ) {

			if ( $this->is_fresh_install() ) {
				return self::INITIAL_DB_VERSION;
			}

			throw new MigrationException( 'Failed to resolve starting database version.', previous: $e );
		} catch ( InvalidArgumentException | StorageExceptionInterface $e ) {
			throw new MigrationException( 'Failed to resolve starting database version.', previous: $e );
		}
	}

	/**
	 * Checks whether the plugin schema is absent and migrations have not run yet.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when this is a fresh install.
	 *
	 * @throws MigrationException When determining the install state fails.
	 */
	private function is_fresh_install(): bool {

		[ $campaigns_table, $donations_table ] = self::PLUGIN_TABLES;

		try {
			$has_campaigns_table = $this->database->table_exists( $campaigns_table );
			$has_donations_table = $this->database->table_exists( $donations_table );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new MigrationException( 'Failed to determine whether this is a fresh install.', previous: $e );
		}

		return ! $has_campaigns_table && ! $has_donations_table;
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
		} catch ( DatabaseExceptionInterface $e ) {
			$this->logger->log_charset_collate_failed( $e );
			throw new MigrationException( 'Failed to fetch database charset and collation.', previous: $e );
		}
	}

	/**
	 * Stores the given schema version as the current DB version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Provides the version to store.
	 *
	 * @throws StorageExceptionInterface When writing the version fails.
	 */
	private function update_current_db_version( string $version ): void {

		$this->storage->set( self::OPTION_KEY, $version );
	}
}
