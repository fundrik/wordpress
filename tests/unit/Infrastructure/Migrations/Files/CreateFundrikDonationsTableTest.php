<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\DatabasePort;
use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikDonationsTable;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateFundrikDonationsTable::class )]
#[UsesClass( MigrationVersion::class )]
final class CreateFundrikDonationsTableTest extends MockeryTestCase {

	private CreateFundrikDonationsTable $migration;
	private DatabasePort&MockInterface $db;

	protected function setUp(): void {

		parent::setUp();

		$this->db = Mockery::mock( DatabasePort::class );
		$this->migration = new CreateFundrikDonationsTable( $this->db );
	}

	#[Test]
	public function apply_executes_expected_create_table_query(): void {

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';
		$table_name = 'wp_fundrik_donations';

		$this->db
			->shouldReceive( 'qualify_table_name' )
			->once()
			->with( 'fundrik_donations' )
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
						&& str_contains( $sql, '`id` CHAR(36) NOT NULL' )
						&& str_contains( $sql, '`campaign_id` BIGINT UNSIGNED NOT NULL' )
						&& str_contains( $sql, '`created_at` DATETIME(6) NOT NULL' )
						&& str_contains( $sql, 'KEY `campaign_id` (`campaign_id`)' ),
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
			->with( 'fundrik_donations' )
			->andReturn( 'wp_fundrik_donations' );

		$this->db
			->shouldReceive( 'query_with_args' )
			->once()
			->andThrow( new FakeDatabaseException( 'DB failed' ) );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Cannot create the "wp_fundrik_donations" table.' );

		$this->migration->apply( $charset_collate );
	}

	#[Test]
	public function it_has_the_migration_version_attribute_with_expected_value(): void {

		$this->assert_class_has_attribute(
			class_name: CreateFundrikDonationsTable::class,
			attribute_class: MigrationVersion::class,
			expected_values: [ 'value' => '2026_03_04_00' ],
		);
	}
}
