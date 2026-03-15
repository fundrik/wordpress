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
	public function it_exposes_expected_migration_classes(): void {

		$this->assertSame(
			[
				CreateFundrikCampaignsTable::class,
				CreateFundrikDonationsTable::class,
			],
			MigrationDefinitions::classes(),
		);
	}
}
