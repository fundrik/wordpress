<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikCampaignsTable;
use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikDonationsTable;

/**
 * Provides migration declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class MigrationDefinitions {

	/**
	 * Returns the configured migration classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 *
	 * @phpstan-return list<class-string<AbstractMigration>>
	 */
	public static function classes(): array {

		return [
			CreateFundrikCampaignsTable::class,
			CreateFundrikDonationsTable::class,
		];
	}
}
