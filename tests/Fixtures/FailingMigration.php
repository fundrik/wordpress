<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;

/**
 * Provides a migration that always fails to apply.
 */
#[MigrationVersion( '2025_12_27_00' )]
final readonly class FailingMigration extends AbstractMigration {

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
