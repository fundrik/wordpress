<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Bootstrap\Container\ContainerInterface;
use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRegistry;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunnerLogger;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersion;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationVersionReader;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Tests\Fixtures\FailingMigration;
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
#[UsesClass( MigrationRunnerLogger::class )]
#[UsesClass( MigrationVersion::class )]
#[UsesClass( MigrationVersionReader::class )]
final class MigrationRunnerTest extends MockeryTestCase {

	private ContainerInterface&MockInterface $container;
	private DatabaseInterface&MockInterface $database;
	private StorageInterface&MockInterface $storage;
	private MigrationRegistry&MockInterface $registry;

	private LoggerInterface&MockInterface $psr_logger;
	private MigrationRunnerLogger $logger;

	private MigrationVersionReader $version_reader;
	private MigrationRunner $runner;

	protected function setUp(): void {

		parent::setUp();

		$this->container = Mockery::mock( ContainerInterface::class );
		$this->database = Mockery::mock( DatabaseInterface::class );
		$this->storage = Mockery::mock( StorageInterface::class );
		$this->registry = Mockery::mock( MigrationRegistry::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new MigrationRunnerLogger( $this->psr_logger );

		$this->version_reader = new MigrationVersionReader();

		$this->runner = new MigrationRunner(
			$this->container,
			$this->database,
			$this->storage,
			$this->version_reader,
			$this->registry,
			$this->logger,
		);
	}

	// Helpers
	// ---------------------------------------------------------------------

	/**
	 * Arranges the common migration environment for tests.
	 *
	 * Note: the runner reads the current DB version multiple times, so the test passes
	 * an explicit sequence of versions returned by StorageInterface::get().
	 *
	 * @param array<string> $current_versions Provides the sequential stored DB versions.
	 * @param string $target_version Provides the target DB version available in the registry.
	 * @param bool $expect_migration_classes Whether the runner is expected to request migration classes.
	 * @param array<string> $migration_classes Provides the migration classes returned by the registry.
	 * @param bool $expect_charset_collate Whether the runner is expected to request charset/collate.
	 * @param string $charset_collate Provides the charset/collate string returned by the database.
	 *
	 * @phpstan-param non-empty-list<string> $current_versions
	 * @phpstan-param array<class-string<AbstractMigration>> $migration_classes
	 */
	private function arrange_migration_environment(
		array $current_versions,
		string $target_version = '2400_01_12_01',
		bool $expect_migration_classes = false,
		array $migration_classes = [],
		bool $expect_charset_collate = false,
		string $charset_collate = 'utf8mb4_unicode_ci',
	): void {

		$this->registry
			->shouldReceive( 'get_target_db_version' )
			->once()
			->andReturn( $target_version );

		$this->storage
			->shouldReceive( 'get' )
			->times( count( $current_versions ) )
			->with( 'fundrik_db_version', '0000_00_00_00' )
			// phpcs:ignore SlevomatCodingStandard.Operators.SpreadOperatorSpacing.IncorrectSpacesAfterOperator
			->andReturn( ...$current_versions );

		if ( $expect_charset_collate ) {
			$this->database
				->shouldReceive( 'get_charset_collate' )
				->once()
				->andReturn( $charset_collate );
		} else {
			$this->database->shouldNotReceive( 'get_charset_collate' );
		}

		if ( $expect_migration_classes ) {
			$this->registry
				->shouldReceive( 'get_migration_classes' )
				->once()
				->andReturn( $migration_classes );
		} else {
			$this->registry->shouldNotReceive( 'get_migration_classes' );
		}
	}

	// Skips
	// ---------------------------------------------------------------------

	#[Test]
	public function it_skips_migration_if_current_version_is_equal(): void {

		$this->arrange_migration_environment(
			current_versions: [ '2400_01_12_01' ],
			target_version: '2400_01_12_01',
		);

		$this->container->shouldNotReceive( 'make' );
		$this->storage->shouldNotReceive( 'set' );

		$this->psr_logger->shouldNotReceive( 'info' );
		$this->psr_logger->shouldNotReceive( 'warning' );
		$this->psr_logger->shouldNotReceive( 'error' );

		$this->runner->migrate();
	}

	#[Test]
	public function it_skips_migration_if_current_version_is_newer(): void {

		$this->arrange_migration_environment(
			current_versions: [ '2500_01_01_00' ],
			target_version: '2400_01_12_01',
		);

		$this->container->shouldNotReceive( 'make' );
		$this->storage->shouldNotReceive( 'set' );

		$this->psr_logger->shouldNotReceive( 'info' );
		$this->psr_logger->shouldNotReceive( 'warning' );
		$this->psr_logger->shouldNotReceive( 'error' );

		$this->runner->migrate();
	}

	// Applies and updates
	// ---------------------------------------------------------------------

	#[Test]
	public function it_applies_pending_migrations_in_correct_order_and_updates_db_version(): void {

		TestMigrationTrace::reset();

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';

		$old = new OldMigration( $this->database );
		$new1 = new NewMigration1( $this->database );
		$new2 = new NewMigration2( $this->database );

		$old_class = $old::class;
		$new1_class = $new1::class;
		$new2_class = $new2::class;

		$this->arrange_migration_environment(
			current_versions: [ '2025_06_14_00', '2400_01_12_01' ],
			target_version: '2400_01_12_01',
			expect_migration_classes: true,
			migration_classes: [ $old_class, $new2_class, $new1_class ], // wrong order on purpose.
			expect_charset_collate: true,
			charset_collate: $charset_collate,
		);

		$this->container
			->shouldReceive( 'make' )
			->twice()
			->andReturnUsing(
				static fn ( string $class ) => match ( $class ) {
					$new1_class => $new1,
					$new2_class => $new2,
					default => throw new RuntimeException( "Unexpected class requested: {$class}" ),
				},
			);

		$this->storage
			->shouldReceive( 'set' )
			->twice()
			->andReturn( true );

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Running migrations completed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunner::class,
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
			[ NewMigration1::class, NewMigration2::class ],
			TestMigrationTrace::$calls,
		);
	}

	// Errors
	// ---------------------------------------------------------------------

	#[Test]
	public function it_throws_when_charset_collate_cannot_be_determined(): void {

		$e = new DatabaseException( 'No charset' );

		$this->arrange_migration_environment(
			current_versions: [ '0000_00_00_00' ],
			target_version: '2400_01_12_01',
			expect_migration_classes: false,
			expect_charset_collate: false,
		);

		$this->registry->shouldNotReceive( 'get_migration_classes' );
		$this->container->shouldNotReceive( 'make' );
		$this->storage->shouldNotReceive( 'set' );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Fetching database charset/collation failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunner::class,
						'logger_class' => MigrationRunnerLogger::class,
						'component' => 'migrations',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'get_charset_collate',
						'outcome' => 'failed',
						'exception' => $e,
					],
				),
			);

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Cannot determine database charset and collation.' );

		$this->runner->migrate();
	}

	#[Test]
	public function it_logs_and_rethrows_when_migration_application_fails(): void {

		$migration = new FailingMigration( $this->database );
		$migration_class = $migration::class;
		$migration_version = $this->version_reader->get_version( $migration_class );

		$this->arrange_migration_environment(
			current_versions: [ '0000_00_00_00' ],
			target_version: '2400_01_12_01',
			expect_migration_classes: true,
			migration_classes: [ $migration_class ],
			expect_charset_collate: true,
			charset_collate: 'utf8mb4_unicode_ci',
		);

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( $migration_class )
			->andReturn( $migration );

		$this->storage->shouldNotReceive( 'set' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Applying migration failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunner::class,
						'logger_class' => MigrationRunnerLogger::class,
						'component' => 'migrations',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'apply_migration',
						'outcome' => 'failed',
						'migration_class' => $migration_class,
						'migration_version' => $migration_version,
					],
				),
			);

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage( 'Test migration failed.' );

		$this->runner->migrate();
	}

	// Warnings
	// ---------------------------------------------------------------------

	#[Test]
	public function it_logs_warning_if_version_update_fails(): void {

		TestMigrationTrace::reset();

		$migration = new NewMigration1( $this->database );
		$migration_class = $migration::class;
		$migration_version = $this->version_reader->get_version( $migration_class );

		$this->arrange_migration_environment(
			current_versions: [ '0000_00_00_00', '0000_00_00_00' ], // still old because update failed.
			target_version: '2400_01_12_01',
			expect_migration_classes: true,
			migration_classes: [ $migration_class ],
			expect_charset_collate: true,
			charset_collate: 'utf8mb4_unicode_ci',
		);

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( $migration_class )
			->andReturn( $migration );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( 'fundrik_db_version', $migration_version )
			->andReturn( false );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Updating stored DB version failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunner::class,
						'logger_class' => MigrationRunnerLogger::class,
						'component' => 'migrations',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'update_db_version',
						'outcome' => 'failed',
						'migration_class' => $migration_class,
						'migration_version' => $migration_version,
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
						'operation' => 'migrate',
						'outcome' => 'applied',
						'applied' => 1,
						'from_version' => '0000_00_00_00',
						'to_version' => '0000_00_00_00',
						'target_version' => '2400_01_12_01',
					],
				),
			);

		$this->runner->migrate();

		$this->assertSame( [ NewMigration1::class ], TestMigrationTrace::$calls );
	}
}
