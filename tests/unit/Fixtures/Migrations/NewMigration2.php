<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Override;

/**
 * Represents the second pending migration in runner tests.
 */
final readonly class NewMigration2 extends AbstractTestMigration {

	#[Override]
	protected static function define_version(): string {

		return '2026_01_01_01';
	}
}
