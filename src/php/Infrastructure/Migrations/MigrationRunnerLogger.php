<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Psr\Log\LoggerInterface;

/**
 * Provides structured, platform-aware logging for the MigrationRunner.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class MigrationRunnerLogger extends Logger {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for migration operations.
	 */
	public function __construct(
		LoggerInterface $logger,
	) {

		parent::__construct( $logger, 'migrations', 'infrastructure' );

		$this->set_service_class( MigrationRunnerPort::class );
	}

	/**
	 * Logs a failure to fetch the database charset/collation string (error).
	 *
	 * @since 1.0.0
	 *
	 * @param DatabaseExceptionInterface $e The database exception thrown during charset/collation lookup.
	 */
	public function log_charset_collate_failed( DatabaseExceptionInterface $e ): void {

		$this->log_error(
			'Fetching database charset/collation failed.',
			[
				'operation' => 'get_charset_collate',
				'outcome' => 'failed',
				'exception' => $e,
			],
		);
	}

	/**
	 * Logs that a migration was applied successfully (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version.
	 */
	public function log_migration_applied( string $class_name, string $version ): void {

		$this->log_debug(
			'Applying migration succeeded.',
			[
				'operation' => 'apply_migration',
				'outcome' => 'applied',
				'migration_class' => $class_name,
				'migration_version' => $version,
			],
		);
	}

	/**
	 * Logs failure to update the stored DB version after a successful migration (error).
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version.
	 */
	public function log_db_version_update_failed( string $class_name, string $version ): void {

		$this->log_error(
			'Updating stored DB version failed.',
			[
				'operation' => 'update_db_version',
				'outcome' => 'failed',
				'migration_class' => $class_name,
				'migration_version' => $version,
			],
		);
	}

	/**
	 * Logs a migration failure (error).
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version.
	 * @param MigrationException $e The exception thrown during migration.
	 */
	public function log_migration_failed( string $class_name, string $version, MigrationException $e ): void {

		$this->log_error(
			'Applying migration failed.',
			[
				'operation' => 'apply_migration',
				'outcome' => 'failed',
				'migration_class' => $class_name,
				'migration_version' => $version,
				'exception' => $e,
			],
		);
	}

	/**
	 * Logs completion of the whole migration process (info).
	 *
	 * @since 1.0.0
	 *
	 * @param int $applied The number of applied migrations.
	 * @param string $from The version before running migrations.
	 * @param string $to The version after running migrations.
	 * @param string $target The target database version.
	 */
	public function log_migrations_completed( int $applied, string $from, string $to, string $target ): void {

		$this->log_info(
			'Running migrations completed.',
			[
				'operation' => 'migrate',
				'outcome' => $applied > 0 ? 'applied' : 'skipped',
				'applied' => $applied,
				'from_version' => $from,
				'to_version' => $to,
				'target_version' => $target,
			],
		);
	}
}
