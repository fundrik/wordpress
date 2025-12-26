<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Psr\Log\LoggerInterface;

/**
 * Provides structured, platform-aware logging for the MigrationRunner.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class MigrationRunnerLogger {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for migration operations.
	 */
	public function __construct(
		private LoggerInterface $logger,
	) {}

	/**
	 * Logs the start of the migration process (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $current The current database version.
	 * @param string $target The target database version.
	 */
	public function log_migrations_start( string $current, string $target ): void {

		$this->logger->debug(
			'Running migrations started.',
			$this->logger_context(
				extra: [
					'operation' => 'migrate',
					'current_version' => $current,
					'target_version' => $target,
				],
			),
		);
	}

	/**
	 * Logs that no migrations are required (debug).
	 *
	 * @since 1.0.0
	 */
	public function log_no_migrations_needed(): void {

		$this->logger->debug(
			'Running migrations skipped (already up to date).',
			$this->logger_context(
				extra: [
					'operation' => 'migrate',
					'outcome' => 'skipped',
				],
			),
		);
	}

	/**
	 * Logs a failure to fetch the database charset/collation string (error).
	 *
	 * @since 1.0.0
	 *
	 * @param DatabaseException $e The database exception thrown during charset/collation lookup.
	 */
	public function log_charset_collate_failed( DatabaseException $e ): void {

		$this->logger->error(
			'Fetching database charset/collation failed.',
			$this->logger_context(
				extra: [
					'operation' => 'get_charset_collate',
					'outcome' => 'failed',
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs that a migration will be applied (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version.
	 */
	public function log_migration_applying( string $class_name, string $version ): void {

		$this->logger->debug(
			'Applying migration started.',
			$this->logger_context(
				extra: [
					'operation' => 'apply_migration',
				],
				class_name: $class_name,
				version: $version,
			),
		);
	}

	/**
	 * Logs that a migration was skipped as already applied (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version.
	 */
	public function log_migration_skipped( string $class_name, string $version ): void {

		$this->logger->debug(
			'Applying migration skipped (already applied).',
			$this->logger_context(
				extra: [
					'operation' => 'apply_migration',
					'outcome' => 'skipped',
				],
				class_name: $class_name,
				version: $version,
			),
		);
	}

	/**
	 * Logs that a migration completed; includes whether version was updated (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version.
	 * @param bool $version_updated Whether the stored DB version was updated.
	 */
	public function log_migration_applied( string $class_name, string $version, bool $version_updated ): void {

		$this->logger->debug(
			$version_updated
				? 'Applying migration succeeded and version updated.'
				: 'Applying migration succeeded but version update failed.',
			$this->logger_context(
				extra: [
					'operation' => 'apply_migration',
					'outcome' => 'applied',
					'version_updated' => $version_updated,
				],
				class_name: $class_name,
				version: $version,
			),
		);
	}

	/**
	 * Logs failure to update the stored DB version after a successful migration (warning).
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The migration class name.
	 * @param string $version The migration version.
	 */
	public function log_db_version_update_failed( string $class_name, string $version ): void {

		$this->logger->warning(
			'Updating stored DB version failed.',
			$this->logger_context(
				extra: [
					'operation' => 'update_db_version',
					'outcome' => 'failed',
				],
				class_name: $class_name,
				version: $version,
			),
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

		$this->logger->error(
			'Applying migration failed.',
			$this->logger_context(
				extra: [
					'operation' => 'apply_migration',
					'outcome' => 'failed',
					'exception' => $e,
				],
				class_name: $class_name,
				version: $version,
			),
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

		$this->logger->info(
			'Running migrations completed.',
			$this->logger_context(
				extra: [
					'operation' => 'migrate',
					'outcome' => $applied ? 'applied' : 'skipped',
					'applied' => $applied,
					'from_version' => $from,
					'to_version' => $to,
					'target_version' => $target,
				],
			),
		);
	}

	/**
	 * Builds structured logging context for the migration runner.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $extra Additional context entries to merge.
	 * @param string|null $class_name Optional migration class name.
	 * @param string|null $version Optional migration version.
	 *
	 * @return array<string,mixed> The structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( array $extra = [], ?string $class_name = null, ?string $version = null, ): array {

		$base = [
			'service_class' => MigrationRunner::class,
			'logger_class' => self::class,
			'component' => 'migrations',
			'layer' => 'infrastructure',
			'system' => 'wordpress',
		];

		if ( $class_name !== null ) {
			$base['migration_class'] = $class_name;
		}

		if ( $version !== null ) {
			$base['migration_version'] = $version;
		}

		return $base + $extra;
	}
}
