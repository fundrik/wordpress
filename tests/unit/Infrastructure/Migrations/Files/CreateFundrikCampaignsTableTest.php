<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikCampaignsTable;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CreateFundrikCampaignsTable::class )]
final class CreateFundrikCampaignsTableTest extends MockeryTestCase {

	private CreateFundrikCampaignsTable $migration;
	private DatabasePort&MockInterface $db;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabasePort::class );
		$this->migration = new CreateFundrikCampaignsTable( $this->db );
	}

	#[Test]
	public function apply_executes_expected_create_table_query(): void {

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
		$table_name = 'wp_fundrik_campaigns';

		$this->db
			->shouldReceive( 'qualify_table_name' )
			->once()
			->with( 'fundrik_campaigns' )
			->andReturn( $table_name );

		$this->db
			->shouldReceive( 'query_with_args' )
			->once()
			->with(
				Mockery::on(
					static fn ( string $sql ): bool => str_contains(
						$sql,
						'CREATE TABLE IF NOT EXISTS %i',
					)
						&& str_contains( $sql, $charset_collate )
						&& str_contains( $sql, '`version` INT UNSIGNED NOT NULL' )
						&& str_contains( $sql, '`currency_code` CHAR(3) NOT NULL' )
						&& str_contains( $sql, '`target_amount` INT UNSIGNED NULL' )
						&& str_contains( $sql, '`created_at` DATETIME NOT NULL' )
						&& str_contains( $sql, '`updated_at` DATETIME NULL' )
						&& str_contains( $sql, 'PRIMARY KEY (`id`)' ),
				),
				$table_name,
			);

		$this->migration->apply( $charset_collate );
	}

	#[Test]
	public function apply_throws_when_query_throws_database_exception(): void {

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';

		$this->db
			->shouldReceive( 'qualify_table_name' )
			->once()
			->with( 'fundrik_campaigns' )
			->andReturn( 'wp_fundrik_campaigns' );

		$this->db
			->shouldReceive( 'query_with_args' )
			->once()
			->andThrow( new FakeDatabaseException( 'DB failed' ) );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Failed to create table "wp_fundrik_campaigns".' );

		$this->migration->apply( $charset_collate );
	}

	#[Test]
	public function it_exposes_the_expected_migration_version(): void {

		$this->assertSame( '2026_03_21_00', CreateFundrikCampaignsTable::version() );
	}
}
