<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Container\ContainerInterface;
use Fundrik\WordPress\Infrastructure\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\Helpers\LoggerFormatter;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRegistry;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersionReader;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Tests\Fixtures\NewMigration1;
use Fundrik\WordPress\Tests\Fixtures\NewMigration2;
use Fundrik\WordPress\Tests\Fixtures\OldMigration;
use Fundrik\WordPress\Tests\Fixtures\TestMigrationTrace;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( MigrationRunner::class )]
#[UsesClass( AbstractMigration::class )]
#[UsesClass( MigrationVersion::class )]
#[UsesClass( MigrationVersionReader::class )]
#[UsesClass( LoggerFormatter::class )]
final class MigrationRunnerTest extends MockeryTestCase {

	private ContainerInterface&MockInterface $container;
	private DatabaseInterface&MockInterface $database;
	private StorageInterface&MockInterface $storage;
	private MigrationRegistry&MockInterface $registry;
	private LoggerInterface&MockInterface $logger;
	private MigrationVersionReader $version_reader;
	private MigrationRunner $runner;

	protected function setUp(): void {

		parent::setUp();

		$this->container = Mockery::mock( ContainerInterface::class );
		$this->database = Mockery::mock( DatabaseInterface::class );
		$this->storage = Mockery::mock( StorageInterface::class );
		$this->registry = Mockery::mock( MigrationRegistry::class );
		$this->logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->version_reader = new MigrationVersionReader();

		$this->runner = new MigrationRunner(
			$this->container,
			$this->database,
			$this->storage,
			$this->version_reader,
			$this->registry,
			$this->logger,
		);

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );
	}

	// Skips
	// ---------------------------------------------------------------------

	#[Test]
	public function it_skips_migration_if_current_version_is_equal(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '2400_01_12_01' );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->logger->shouldNotReceive( 'info' );

		$this->runner->migrate();
	}

	#[Test]
	public function it_skips_migration_if_current_version_is_newer(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '2500_01_01_00' );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->logger->shouldNotReceive( 'info' );

		$this->runner->migrate();
	}

	// Applies and updates
	// ---------------------------------------------------------------------

	#[Test]
	public function it_applies_pending_migrations_in_correct_order_and_updates_db_version(): void {

		TestMigrationTrace::reset();

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';

		$this->storage
			->shouldReceive( 'get' )
			->twice()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '2025_06_14_00', '2400_01_12_01' );

		$old = new OldMigration( $this->database );
		$new1 = new NewMigration1( $this->database );
		$new2 = new NewMigration2( $this->database );

		$old_class = $old::class;
		$new1_class = $new1::class;
		$new2_class = $new2::class;

		$this->registry
			->shouldReceive( 'get_migration_classes' )
			->once()
			->andReturn( [ $old_class, $new2_class, $new1_class ] ); // wrong order.

		$this->container
			->shouldReceive( 'get' )
			->andReturnUsing(
				static fn ( string $class ) => match ( $class ) {
				$new1_class => $new1,
				$new2_class => $new2,
				default => throw new RuntimeException( "Unexpected class requested: $class" ),
				},
			);

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( $charset_collate );

		$this->storage
			->shouldReceive( 'set' )
			->twice()
			->andReturn( true );

		$final_version = $this->version_reader->get_version( $new2_class );
		$this->logger
			->shouldReceive( 'info' )
			->once()
			->with(
				Mockery::pattern( '/^Migration process completed:/' ),
				[
					'applied' => 2,
					'from_version' => '2025_06_14_00',
					'to_version' => $final_version,
					'target_version' => '2400_01_12_01',
				],
			);

		$this->runner->migrate();

		$this->assertSame(
			[ NewMigration1::class, NewMigration2::class ],
			TestMigrationTrace::$calls,
		);
	}

	// Warnings
	// ---------------------------------------------------------------------

	#[Test]
	public function it_logs_warning_if_version_update_fails(): void {

		TestMigrationTrace::reset();

		$migration = new NewMigration1( $this->database );
		$migration_class = $migration::class;
		$migration_version = $this->version_reader->get_version( $migration_class );

		$this->storage
			->shouldReceive( 'get' )
			->twice()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '0000_00_00_00', '0000_00_00_00' );

		$this->registry
			->shouldReceive( 'get_migration_classes' )
			->once()
			->andReturn( [ $migration_class ] );

		$this->container
			->shouldReceive( 'get' )
			->once()
			->with( $migration_class )
			->andReturn( $migration );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', $migration_version )
			->andReturn( false );

		$this->logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Failed to update stored DB version after migration.',
				[
					'migration_class' => $migration_class,
					'migration_version' => $migration_version,
				],
			);

		$this->runner->migrate();
	}
}
