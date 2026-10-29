<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Override;

/**
 * Represents the first pending migration in runner tests.
 */
final readonly class NewMigration1 extends AbstractTestMigration {

	#[Override]
	protected static function define_version(): string {

		return '2026_01_01_00';
	}
}
