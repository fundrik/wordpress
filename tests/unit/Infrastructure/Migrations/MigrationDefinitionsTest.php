<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikCampaignsTable;
use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikDonationsTable;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationDefinitions;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( MigrationDefinitions::class )]
final class MigrationDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function classes_returns_migrations_in_expected_order(): void {

		self::assertSame(
			[
				CreateFundrikCampaignsTable::class,
				CreateFundrikDonationsTable::class,
			],
			MigrationDefinitions::classes(),
		);
	}
}
