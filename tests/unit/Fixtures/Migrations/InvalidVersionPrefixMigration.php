<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
final readonly class InvalidVersionPrefixMigration extends AbstractMigration {

	/**
	 * Returns the migration version.
	 *
	 * @return string The sortable migration version.
	 */
	protected static function define_version(): string {

		return 'v2025_01_01_00';
	}

	// phpcs:disable SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction, SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	public function apply( string $charset_collate ): void {}
}
