<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;

/**
 * Provides a migration that always fails to apply.
 */
final readonly class FailingMigration extends AbstractMigration {

	/**
	 * Returns the migration version.
	 *
	 * @return string The sortable migration version.
	 */
	protected static function define_version(): string {

		return '2025_12_27_00';
	}

	/**
	 * Applies the migration and always fails for testing purposes.
	 *
	 * @throws MigrationException When the migration is applied.
	 */
	// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	public function apply( string $charset_collate ): void {

		throw new MigrationException( 'Test migration failed.' );
	}
}
