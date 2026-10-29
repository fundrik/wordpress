<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations\Files;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\Files\CreateFundrikCampaignsTable;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CreateFundrikCampaignsTable::class )]
#[UsesClass( AbstractMigration::class )]
final class CreateFundrikCampaignsTableTest extends MockeryTestCase {

	private const string TABLE_NAME = 'wp_fundrik_campaigns';

	private DatabasePort&MockInterface $database;

	private CreateFundrikCampaignsTable $migration;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->migration = new CreateFundrikCampaignsTable( $this->database );
	}

	#[Test]
	public function version_returns_expected_version(): void {

		self::assertSame( '2026_03_21_00', CreateFundrikCampaignsTable::version() );
	}

	#[Test]
	public function apply_creates_campaigns_table_with_expected_schema(): void {

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';

		$this->database
			->shouldReceive( 'qualify_table_name' )
			->once()
			->with( 'fundrik_campaigns' )
			->andReturn( self::TABLE_NAME );

		$this->database
			->shouldReceive( 'query_with_args' )
			->once()
			->with(
				Mockery::on(
					static function ( string $sql ) use ( $charset_collate ): bool {

						$schema_fragments = [
							'CREATE TABLE IF NOT EXISTS %i',
							'`id` BIGINT UNSIGNED NOT NULL',
							'`version` INT UNSIGNED NOT NULL',
							'`title` TEXT NOT NULL',
							'`accepts_donations` TINYINT(1) NOT NULL',
							'`currency_code` CHAR(3) NOT NULL',
							'`target_amount` INT UNSIGNED NULL',
							'`collected_amount` INT UNSIGNED NOT NULL',
							'`donations_count` INT UNSIGNED NOT NULL',
							'`created_at` DATETIME NOT NULL',
							'`updated_at` DATETIME NULL',
							'PRIMARY KEY (`id`)',
							sprintf( 'ENGINE=InnoDB %s;', $charset_collate ),
						];

						foreach ( $schema_fragments as $fragment ) {

							if ( ! str_contains( $sql, $fragment ) ) {
								return false;
							}
						}

						return true;
					},
				),
				self::TABLE_NAME,
			);

		$this->migration->apply( $charset_collate );
	}

	#[Test]
	public function apply_throws_migration_exception_when_table_creation_fails(): void {

		$this->database
			->shouldReceive( 'qualify_table_name' )
			->once()
			->with( 'fundrik_campaigns' )
			->andReturn( self::TABLE_NAME );

		$database_exception = new FakeDatabaseException( 'Database failed.' );

		$this->database
			->shouldReceive( 'query_with_args' )
			->once()
			->andThrow( $database_exception );

		try {
			$this->migration->apply( 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );
			self::fail( 'Expected the migration to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame(
				sprintf( 'Failed to create table "%s".', self::TABLE_NAME ),
				$exception->getMessage(),
			);
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}
}
