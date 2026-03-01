<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\DatabaseException;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikCampaignsTable;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateFundrikCampaignsTable::class )]
#[UsesClass( MigrationVersion::class )]
final class CreateFundrikCampaignsTableTest extends MockeryTestCase {

	private CreateFundrikCampaignsTable $migration;
	private DatabaseInterface&MockInterface $db;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabaseInterface::class );
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
						&& str_contains( $sql, '`target_currency` CHAR(3) NOT NULL' )
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
			->andThrow( new DatabaseException( 'DB failed' ) );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Cannot create the "wp_fundrik_campaigns" table.' );

		$this->migration->apply( $charset_collate );
	}

	#[Test]
	public function it_has_the_migration_version_attribute_with_expected_value(): void {

		$this->assert_class_has_attribute(
			class_name: CreateFundrikCampaignsTable::class,
			attribute_class: MigrationVersion::class,
			expected_values: [ 'value' => '2025_06_15_00' ],
		);
	}
}
