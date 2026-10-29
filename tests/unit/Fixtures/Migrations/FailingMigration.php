<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Override;

/**
 * Represents a migration that fails when applied.
 */
final readonly class FailingMigration extends AbstractMigration {

	#[Override]
	// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter -- Required by AbstractMigration for a failing fixture.
	public function apply( string $charset_collate ): void {

		throw new MigrationException( 'Test migration failed.' );
	}

	#[Override]
	protected static function define_version(): string {

		return '2026_01_01_00';
	}
}
