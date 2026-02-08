<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikCampaignsTable;

/**
 * Provides the list of available migration classes and the target schema version.
 *
 * @since 1.0.0
 *
 * @internal
 */
class MigrationRegistry {

	private const string TARGET_VERSION = '2025_06_15_00';

	/**
	 * Returns all migration class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> The list of migration class names.
	 *
	 * @phpstan-return list<class-string<AbstractMigration>>
	 */
	public function get_migration_classes(): array {

		return [
			CreateFundrikCampaignsTable::class,
		];
	}

	/**
	 * Returns the latest expected database schema version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The target database schema version.
	 */
	public function get_target_db_version(): string {

		return self::TARGET_VERSION;
	}
}
