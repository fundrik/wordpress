<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Override;

/**
 * Represents a migration with a duplicate version in runner tests.
 */
final readonly class DuplicateVersionMigration extends AbstractTestMigration {

	#[Override]
	protected static function define_version(): string {

		return '2026_01_01_00';
	}
}
