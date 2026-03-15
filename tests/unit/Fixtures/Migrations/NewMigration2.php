<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
final readonly class NewMigration2 extends AbstractMigration {

	/**
	 * Returns the migration version.
	 *
	 * @return string The sortable migration version.
	 */
	protected static function define_version(): string {

		return '2400_01_12_01';
	}

	// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	public function apply( string $charset_collate ): void {

		TestMigrationTrace::$calls[] = self::class;
	}
}
