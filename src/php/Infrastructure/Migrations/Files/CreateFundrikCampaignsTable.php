<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;

/**
 * Creates the `fundrik_campaigns` table in the database.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CreateFundrikCampaignsTable extends AbstractMigration {

	/**
	 * Returns the migration version.
	 *
	 * @since 1.0.0
	 *
	 * @return string The sortable migration version.
	 */
	protected static function define_version(): string {

		return '2026_03_21_00';
	}

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

		$table_name = $this->database->qualify_table_name( 'fundrik_campaigns' );

		$sql = "
			CREATE TABLE IF NOT EXISTS %i (
				`id` BIGINT UNSIGNED NOT NULL,
				`version` INT UNSIGNED NOT NULL,
				`title` TEXT NOT NULL,
				`is_open` TINYINT(1) NOT NULL,
				`currency_code` CHAR(3) NOT NULL,
				`target_amount` INT UNSIGNED NULL,
				`created_at` DATETIME NOT NULL,
				`updated_at` DATETIME NULL,
				PRIMARY KEY (`id`)
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
