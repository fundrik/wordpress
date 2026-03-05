<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikCampaignsTable;
use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikDonationsTable;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRegistry;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( MigrationRegistry::class )]
final class MigrationRegistryTest extends FundrikTestCase {

	private MigrationRegistry $registry;

	protected function setUp(): void {

		parent::setUp();

		$this->registry = new MigrationRegistry();
	}

	#[Test]
	public function it_returns_the_expected_target_db_version(): void {

		$this->assertSame( '2026_03_04_00', $this->registry->get_target_db_version() );
	}

	#[Test]
	public function it_returns_all_expected_migration_classes_in_correct_order(): void {

		$this->assertSame(
			[
				CreateFundrikCampaignsTable::class,
				CreateFundrikDonationsTable::class,
			],
			$this->registry->get_migration_classes(),
		);
	}
}
