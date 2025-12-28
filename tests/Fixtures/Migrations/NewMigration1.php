<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;

#[MigrationVersion( '2400_01_12_00' )]
final readonly class NewMigration1 extends AbstractMigration {

	// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	public function apply( string $charset_collate ): void {

		TestMigrationTrace::$calls[] = self::class;
	}
}
