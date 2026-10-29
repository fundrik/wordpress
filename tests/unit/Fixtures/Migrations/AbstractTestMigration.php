<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Override;

/**
 * Records application of a migration for runner tests.
 */
abstract readonly class AbstractTestMigration extends AbstractMigration {

	#[Override]
	public function apply( string $charset_collate ): void {

		TestMigrationTrace::record( static::class, $charset_collate );
	}
}
