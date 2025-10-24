<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;

/**
 * Creates the `fundrik_campaigns` table in the database.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[MigrationVersion( '2025_06_15_00' )]
final readonly class CreateFundrikCampaignsTable extends AbstractMigration {

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies the table creation schema for campaign data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $charset_collate The charset and collation string for table creation.
	 *
	 * @throws MigrationException When the table cannot be created.
	 */
	public function apply( string $charset_collate ): void {

		$sql = "
			CREATE TABLE IF NOT EXISTS `fundrik_campaigns` (
				`id` BIGINT UNSIGNED NOT NULL,
				`title` TEXT NOT NULL,
				`slug` VARCHAR(200) NOT NULL,
				`is_active` TINYINT(1) NOT NULL DEFAULT 0,
				`is_open` TINYINT(1) NOT NULL DEFAULT 0,
				`has_target` TINYINT(1) NOT NULL DEFAULT 0,
				`target_amount` INT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `slug` (`slug`(191))
			) ENGINE=InnoDB {$charset_collate};
		";

		try {
			$this->database->query( $sql );
		} catch ( DatabaseException $e ) {

			throw new MigrationException(
				sprintf( 'Cannot create fundrik_campaigns table: %s', $e->getMessage() ),
				previous: $e,
			);
		}
	}
	// phpcs:enable
}
