<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunnerLogger;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\Fixtures\FakeStorageException;
use Fundrik\WordPress\Tests\Fixtures\FakeStorageNotFoundException;
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
final class MigrationRunnerTest extends MockeryTestCase {

	private DatabasePort&MockInterface $database;
	private StoragePort&MockInterface $storage;

	private LoggerInterface&MockInterface $psr_logger;
	private MigrationRunnerLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->storage = Mockery::mock( StoragePort::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new MigrationRunnerLogger( $this->psr_logger );
	}

	// ---------------------------------------------------------------------
	// Skips
	// ---------------------------------------------------------------------

	#[Test]
	public function it_skips_migration_if_current_version_is_equal(): void {

		$runner = $this->create_runner(
			new NewMigration1( $this->database ),
			new NewMigration2( $this->database ),
		);

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( '2400_01_12_01' );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->storage->shouldNotReceive( 'set' );

		$runner->migrate();
	}

	#[Test]
	public function it_skips_migration_if_current_version_is_newer(): void {

		$runner = $this->create_runner(
			new NewMigration1( $this->database ),
			new NewMigration2( $this->database ),
		);

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( '2500_01_01_00' );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->storage->shouldNotReceive( 'set' );

		$runner->migrate();
	}

	#[Test]
	public function it_uses_the_initial_version_when_the_stored_db_version_is_missing(): void {

		TestMigrationTrace::reset();
		$runner = $this->create_runner( new NewMigration1( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		$this->database
			->shouldReceive( 'table_exists' )
			->once()
			->with( 'fundrik_campaigns' )
			->andReturn( false );

		$this->database
			->shouldReceive( 'table_exists' )
			->once()
			->with( 'fundrik_donations' )
			->andReturn( false );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', NewMigration1::version() )
			->andReturnNull();

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Applying migration succeeded.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'operation' => 'apply_migration',
						'outcome' => 'applied',
						'migration_class' => NewMigration1::class,
						'migration_version' => NewMigration1::version(),
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
						'operation' => 'migrate',
						'outcome' => 'applied',
						'applied' => 1,
						'from_version' => '0000_00_00_00',
						'to_version' => NewMigration1::version(),
						'target_version' => NewMigration1::version(),
					],
				),
			);

		$runner->migrate();

		$this->assertSame( [ NewMigration1::class ], TestMigrationTrace::$calls );
	}

	// ---------------------------------------------------------------------
	// Applies and updates
	// ---------------------------------------------------------------------

	#[Test]
	public function it_applies_pending_migrations_in_correct_order_and_updates_db_version(): void {

		TestMigrationTrace::reset();
		$runner = $this->create_runner(
			new OldMigration( $this->database ),
			new NewMigration2( $this->database ),
			new NewMigration1( $this->database ),
		);

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( '2025_06_14_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( $charset_collate );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', '2400_01_12_00' )
			->andReturnNull();

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', '2400_01_12_01' )
			->andReturnNull();

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

		$runner->migrate();

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
		$runner = $this->create_runner( new NewMigration2( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andThrow( $e );

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

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Failed to fetch database charset and collation.' );

		$runner->migrate();
	}

	#[Test]
	public function it_throws_when_the_current_db_version_cannot_be_read(): void {

		$runner = $this->create_runner( new NewMigration1( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andThrow( new FakeStorageException( 'Read failed.' ) );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->database->shouldNotReceive( 'table_exists' );
		$this->storage->shouldNotReceive( 'set' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Failed to resolve starting database version.' );

		$runner->migrate();
	}

	#[Test]
	public function it_throws_when_the_current_db_version_has_an_invalid_type(): void {

		$runner = $this->create_runner( new NewMigration1( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( 123 );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->database->shouldNotReceive( 'table_exists' );
		$this->storage->shouldNotReceive( 'set' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Failed to resolve starting database version.' );

		$runner->migrate();
	}

	#[Test]
	public function it_throws_when_the_stored_db_version_is_missing_for_a_non_fresh_install(): void {

		$runner = $this->create_runner( new NewMigration1( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		$this->database
			->shouldReceive( 'table_exists' )
			->once()
			->with( 'fundrik_campaigns' )
			->andReturn( false );

		$this->database
			->shouldReceive( 'table_exists' )
			->once()
			->with( 'fundrik_donations' )
			->andReturn( true );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->storage->shouldNotReceive( 'set' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Failed to resolve starting database version.' );

		$runner->migrate();
	}

	#[Test]
	public function it_throws_when_it_cannot_determine_whether_this_is_a_fresh_install(): void {

		$runner = $this->create_runner( new NewMigration1( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		$this->database
			->shouldReceive( 'table_exists' )
			->once()
			->with( 'fundrik_campaigns' )
			->andThrow( new FakeDatabaseException( 'Query failed.' ) );

		$this->database->shouldNotReceive( 'get_charset_collate' );
		$this->storage->shouldNotReceive( 'set' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Failed to determine whether this is a fresh install.' );

		$runner->migrate();
	}

	#[Test]
	public function it_logs_and_rethrows_when_migration_application_fails(): void {

		$migration_version = FailingMigration::version();
		$runner = $this->create_runner( new FailingMigration( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

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

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Test migration failed.' );

		$runner->migrate();
	}

	#[Test]
	public function it_logs_and_throws_when_db_version_update_fails(): void {

		TestMigrationTrace::reset();

		$migration_version = NewMigration1::version();
		$runner = $this->create_runner( new NewMigration1( $this->database ) );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', $migration_version )
			->andThrow( new FakeStorageException( 'Write failed.' ) );

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

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'was applied, but updating stored DB version failed.' );

		$runner->migrate();

		$this->assertSame( [ NewMigration1::class ], TestMigrationTrace::$calls );
	}

	#[Test]
	public function it_throws_when_two_migrations_have_the_same_version(): void {

		$runner = $this->create_runner(
			new NewMigration1( $this->database ),
			new DuplicateVersionMigration( $this->database ),
		);

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_db_version' )
			->andReturn( '0000_00_00_00' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->storage->shouldNotReceive( 'set' );
		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Migration version must be unique.' );
		$this->expectExceptionMessage( 'Given: 2400_01_12_00.' );

		$runner->migrate();
	}

	/**
	 * Creates a migration runner with the provided migrations.
	 *
	 * @param AbstractMigration ...$migrations The migrations to configure.
	 */
	private function create_runner( AbstractMigration ...$migrations ): MigrationRunner {

		return new MigrationRunner( $this->database, $this->storage, $this->logger, ...$migrations );
	}
}
