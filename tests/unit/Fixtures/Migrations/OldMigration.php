<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Override;

/**
 * Represents an already applied migration in runner tests.
 */
final readonly class OldMigration extends AbstractTestMigration {

	#[Override]
	protected static function define_version(): string {

		return '2025_01_01_00';
	}
}
