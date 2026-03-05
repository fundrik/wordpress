<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;

/**
 * Creates the `fundrik_donations` table in the database.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[MigrationVersion( '2026_03_04_00' )]
final readonly class CreateFundrikDonationsTable extends AbstractMigration {

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies the table creation schema for donation data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $charset_collate The charset and collation string for table creation.
	 *
	 * @throws MigrationException When the table cannot be created.
	 */
	public function apply( string $charset_collate ): void {

		$table_name = $this->database->qualify_table_name( 'fundrik_donations' );

		$sql = "
			CREATE TABLE IF NOT EXISTS %i (
				`id` CHAR(36) NOT NULL,
				`version` INT UNSIGNED NOT NULL,
				`campaign_id` BIGINT UNSIGNED NOT NULL,
				`amount_minor` INT UNSIGNED NOT NULL,
				`currency` CHAR(3) NOT NULL,
				`status` VARCHAR(16) NOT NULL,
				`created_at` DATETIME(6) NOT NULL,
				`captured_at` DATETIME(6) NULL,
				`status_changed_at` DATETIME(6) NULL,
				PRIMARY KEY (`id`),
				KEY `campaign_id` (`campaign_id`)
			) ENGINE=InnoDB {$charset_collate};
		";

		try {
			$this->database->query_with_args( $sql, $table_name );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new MigrationException(
				sprintf( 'Cannot create the "%s" table.', $table_name ),
				previous: $e,
			);
		}
	}
	// phpcs:enable
}
