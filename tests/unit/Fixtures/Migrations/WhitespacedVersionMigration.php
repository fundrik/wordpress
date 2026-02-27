<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;

#[MigrationVersion( ' ' )]
final readonly class WhitespacedVersionMigration extends AbstractMigration {

	// phpcs:disable SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction, SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	public function apply( string $charset_collate ): void {}
}
