<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;

/**
 * Creates the `fundrik_donations` table in the database.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CreateFundrikDonationsTable extends AbstractMigration {

	/**
	 * Returns the migration version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The sortable migration version.
	 */
	protected static function define_version(): string {

		return '2026_03_21_02';
	}

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
				`amount` INT UNSIGNED NOT NULL,
				`currency_code` CHAR(3) NOT NULL,
				`status` VARCHAR(16) NOT NULL,
				`created_at` DATETIME(6) NOT NULL,
				`updated_at` DATETIME(6) NULL,
				PRIMARY KEY (`id`),
				KEY `campaign_id` (`campaign_id`)
			) ENGINE=InnoDB {$charset_collate};
		";

		try {
			$this->database->query_with_args( $sql, $table_name );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new MigrationException(
				sprintf( 'Failed to create table "%s".', $table_name ),
				previous: $e,
			);
		}
	}
	// phpcs:enable
}
