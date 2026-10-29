<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Override;

/**
 * Represents a migration with an invalid version prefix for tests.
 */
final readonly class InvalidVersionPrefixMigration extends AbstractMigration {

	#[Override]
	// phpcs:ignore SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction, SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter -- Required by AbstractMigration for version-only tests.
	public function apply( string $charset_collate ): void {}

	#[Override]
	protected static function define_version(): string {

		return 'v2025_01_01_00';
	}
}
