<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\DatabasePort;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationFactory;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRegistry;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunnerLogger;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersionReader;
use Fundrik\WordPress\Infrastructure\StoragePort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\Fixtures\Migrations\DuplicateVersionMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\FailingMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\NewMigration1;
use Fundrik\WordPress\Tests\Fixtures\Migrations\NewMigration2;
use Fundrik\WordPress\Tests\Fixtures\Migrations\OldMigration;
use Fundrik\WordPress\Tests\Fixtures\Migrations\TestMigrationTrace;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( MigrationRunner::class )]
#[UsesClass( AbstractMigration::class )]
#[UsesClass( MigrationRunnerLogger::class )]
#[UsesClass( MigrationFactory::class )]
#[UsesClass( MigrationVersion::class )]
#[UsesClass( MigrationVersionReader::class )]
final class MigrationRunnerTest extends MockeryTestCase {

	private DatabasePort&MockInterface $database;
	private StoragePort&MockInterface $storage;
	private MigrationRegistry&MockInterface $registry;

	private LoggerInterface&MockInterface $psr_logger;
	private MigrationRunnerLogger $logger;

	private MigrationVersionReader $version_reader;
	private MigrationFactory $migration_factory;

	private MigrationRunner $runner;

	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->storage = Mockery::mock( StoragePort::class );
		$this->registry = Mockery::mock( MigrationRegistry::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new MigrationRunnerLogger( $this->psr_logger );

		$this->version_reader = new MigrationVersionReader();
		$this->migration_factory = new MigrationFactory( $this->database );

		$this->runner = new MigrationRunner(
			$this->database,
			$this->storage,
			$this->version_reader,
			$this->registry,
			$this->logger,
			$this->migration_factory,
		);
	}

	// ---------------------------------------------------------------------
	// Skips
	// ---------------------------------------------------------------------

	#[Test]
	public function it_skips_migration_if_current_version_is_equal(): void {

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '2400_01_12_01' );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->registry->shouldNotReceive( 'get_migration_classes' );
		$this->storage->shouldNotReceive( 'set' );

		$this->psr_logger->shouldNotReceive( 'info' );
		$this->psr_logger->shouldNotReceive( 'debug' );
		$this->psr_logger->shouldNotReceive( 'error' );

		$this->runner->migrate();
	}

	#[Test]
	public function it_skips_migration_if_current_version_is_newer(): void {

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '2500_01_01_00' );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->registry->shouldNotReceive( 'get_migration_classes' );
		$this->storage->shouldNotReceive( 'set' );

		$this->psr_logger->shouldNotReceive( 'info' );
		$this->psr_logger->shouldNotReceive( 'debug' );
		$this->psr_logger->shouldNotReceive( 'error' );

		$this->runner->migrate();
	}

	// ---------------------------------------------------------------------
	// Applies and updates
	// ---------------------------------------------------------------------

	#[Test]
	public function it_applies_pending_migrations_in_correct_order_and_updates_db_version(): void {

		TestMigrationTrace::reset();

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '2025_06_14_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( $charset_collate );

		// wrong order on purpose — the runner must sort by version.
		$this->registry
			->shouldReceive( 'get_migration_classes' )
			->once()
			->andReturn(
				[
					OldMigration::class,
					NewMigration2::class,
					NewMigration1::class,
				],
			);

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', '2400_01_12_00' )
			->andReturn( true );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', '2400_01_12_01' )
			->andReturn( true );

		$this->psr_logger
			->shouldReceive( 'debug' )
			->twice()
			->with(
				'Applying migration succeeded.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'component' => 'migrations',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'apply_migration',
						'outcome' => 'applied',
					],
				),
			);

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Running migrations completed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'component' => 'migrations',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'migrate',
						'outcome' => 'applied',
						'applied' => 2,
						'from_version' => '2025_06_14_00',
						'to_version' => '2400_01_12_01',
						'target_version' => '2400_01_12_01',
					],
				),
			);

		$this->runner->migrate();

		$this->assertSame(
			[
				NewMigration1::class,
				NewMigration2::class,
			],
			TestMigrationTrace::$calls,
		);
	}

	// ---------------------------------------------------------------------
	// Errors
	// ---------------------------------------------------------------------

	#[Test]
	public function it_throws_when_charset_collate_cannot_be_determined(): void {

		$e = new FakeDatabaseException( 'No charset' );

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andThrow( $e );

		$this->registry->shouldNotReceive( 'get_migration_classes' );
		$this->storage->shouldNotReceive( 'set' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Fetching database charset/collation failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'operation' => 'get_charset_collate',
						'outcome' => 'failed',
						'exception' => $e,
					],
				),
			);

		$this->psr_logger->shouldNotReceive( 'info' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Cannot determine database charset and collation.' );

		$this->runner->migrate();
	}

	#[Test]
	public function it_logs_and_rethrows_when_migration_application_fails(): void {

		$migration_version = $this->version_reader->get_version( FailingMigration::class );

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->registry
			->shouldReceive( 'get_migration_classes' )
			->once()
			->andReturn( [ FailingMigration::class ] );

		$this->storage->shouldNotReceive( 'set' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Applying migration failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'operation' => 'apply_migration',
						'outcome' => 'failed',
						'migration_class' => FailingMigration::class,
						'migration_version' => $migration_version,
					],
				),
			);

		$this->psr_logger->shouldNotReceive( 'info' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Test migration failed.' );

		$this->runner->migrate();
	}

	#[Test]
	public function it_logs_and_throws_when_db_version_update_fails(): void {

		TestMigrationTrace::reset();

		$migration_version = $this->version_reader->get_version( NewMigration1::class );

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->registry
			->shouldReceive( 'get_migration_classes' )
			->once()
			->andReturn( [ NewMigration1::class ] );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', $migration_version )
			->andReturn( false );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Updating stored DB version failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'operation' => 'update_db_version',
						'outcome' => 'failed',
						'migration_class' => NewMigration1::class,
						'migration_version' => $migration_version,
					],
				),
			);

		// migration_applied is logged only after successful version update.
		$this->psr_logger->shouldNotReceive( 'debug' );
		$this->psr_logger->shouldNotReceive( 'info' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage(
			'Cannot complete migration: the stored DB version must be updated after applying.',
		);

		$this->runner->migrate();

		$this->assertSame( [ NewMigration1::class ], TestMigrationTrace::$calls );
	}

	#[Test]
	public function it_throws_when_two_migrations_have_the_same_version(): void {

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( '2400_01_12_01' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version', '0000_00_00_00' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->registry
			->shouldReceive( 'get_migration_classes' )
			->once()
			->andReturn(
				[
					NewMigration1::class,
					DuplicateVersionMigration::class, // Same version as NewMigration1.
				],
			);

		$this->storage->shouldNotReceive( 'set' );
		$this->psr_logger->shouldNotReceive( 'info' );
		$this->psr_logger->shouldNotReceive( 'debug' );
		$this->psr_logger->shouldNotReceive( 'error' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Migration version must be unique.' );
		$this->expectExceptionMessage( 'Given: 2400_01_12_00.' );

		$this->runner->migrate();
	}
}
