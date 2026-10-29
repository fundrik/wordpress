<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Infrastructure\Migrations\AbstractMigration;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunner;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunnerLogger;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
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
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( MigrationRunner::class )]
#[UsesClass( AbstractMigration::class )]
#[UsesClass( Logger::class )]
#[UsesClass( MigrationRunnerLogger::class )]
final class MigrationRunnerTest extends MockeryTestCase {

	private const string OPTION_KEY = 'fundrik_db_version';
	private const string INITIAL_VERSION = '0000_00_00_00';

	private DatabasePort&MockInterface $database;
	private StoragePort&MockInterface $storage;

	private LoggerInterface&MockInterface $psr_logger;
	private MigrationRunnerLogger $logger;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		TestMigrationTrace::reset();

		$this->database = Mockery::mock( DatabasePort::class );
		$this->storage = Mockery::mock( StoragePort::class );
		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new MigrationRunnerLogger( $this->psr_logger );
	}

	#[Test]
	#[DataProvider( 'current_version_cases' )]
	public function migrate_skips_when_database_is_already_at_or_above_target( string $current_version ): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andReturn( $current_version );

		$this->create_runner(
			new NewMigration1( $this->database ),
			new NewMigration2( $this->database ),
		)->migrate();
	}

	public static function current_version_cases(): iterable {

		yield 'equal to target' => [ NewMigration2::version() ];
		yield 'newer than target' => [ '2027_01_01_00' ];
	}

	#[Test]
	public function migrate_applies_only_pending_migrations_in_version_order(): void {

		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci';

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andReturn( OldMigration::version() );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( $charset_collate );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( self::OPTION_KEY, NewMigration1::version() )
			->ordered();

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( self::OPTION_KEY, NewMigration2::version() )
			->ordered();

		$this->expect_migration_applied_log( NewMigration1::class, NewMigration1::version() );
		$this->expect_migration_applied_log( NewMigration2::class, NewMigration2::version() );
		$this->expect_migrations_completed_log(
			applied: 2,
			from: OldMigration::version(),
			to: NewMigration2::version(),
			target: NewMigration2::version(),
		);

		$this->create_runner(
			new NewMigration2( $this->database ),
			new OldMigration( $this->database ),
			new NewMigration1( $this->database ),
		)->migrate();

		self::assertSame(
			[
				[ NewMigration1::class, $charset_collate ],
				[ NewMigration2::class, $charset_collate ],
			],
			TestMigrationTrace::calls(),
		);
	}

	#[Test]
	public function migrate_uses_initial_version_for_fresh_install(): void {

		$missing_version = new FakeStorageNotFoundException( 'Missing.' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andThrow( $missing_version );

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
			->with( self::OPTION_KEY, NewMigration1::version() );

		$this->expect_migration_applied_log( NewMigration1::class, NewMigration1::version() );
		$this->expect_migrations_completed_log(
			applied: 1,
			from: self::INITIAL_VERSION,
			to: NewMigration1::version(),
			target: NewMigration1::version(),
		);

		$this->create_runner( new NewMigration1( $this->database ) )->migrate();

		self::assertSame(
			[ [ NewMigration1::class, 'utf8mb4_unicode_ci' ] ],
			TestMigrationTrace::calls(),
		);
	}

	#[Test]
	public function migrate_throws_when_stored_version_cannot_be_read(): void {

		$storage_exception = new FakeStorageException( 'Read failed.' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andThrow( $storage_exception );

		try {
			$this->create_runner( new NewMigration1( $this->database ) )->migrate();
			self::fail( 'Expected the migration runner to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame( 'Failed to resolve starting database version.', $exception->getMessage() );
			self::assertSame( $storage_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function migrate_throws_when_stored_version_has_invalid_type(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andReturn( 123 );

		try {
			$this->create_runner( new NewMigration1( $this->database ) )->migrate();
			self::fail( 'Expected the migration runner to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame( 'Failed to resolve starting database version.', $exception->getMessage() );
			self::assertInstanceOf( InvalidArgumentException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function migrate_throws_when_version_is_missing_for_existing_install(): void {

		$missing_version = new FakeStorageNotFoundException( 'Missing.' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andThrow( $missing_version );

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

		try {
			$this->create_runner( new NewMigration1( $this->database ) )->migrate();
			self::fail( 'Expected the migration runner to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame( 'Failed to resolve starting database version.', $exception->getMessage() );
			self::assertSame( $missing_version, $exception->getPrevious() );
		}
	}

	#[Test]
	public function migrate_throws_when_fresh_install_detection_fails(): void {

		$database_exception = new FakeDatabaseException( 'Query failed.' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		$this->database
			->shouldReceive( 'table_exists' )
			->once()
			->with( 'fundrik_campaigns' )
			->andThrow( $database_exception );

		try {
			$this->create_runner( new NewMigration1( $this->database ) )->migrate();
			self::fail( 'Expected the migration runner to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame( 'Failed to determine whether this is a fresh install.', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function migrate_logs_and_throws_when_charset_collate_cannot_be_read(): void {

		$database_exception = new FakeDatabaseException( 'Charset lookup failed.' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andReturn( self::INITIAL_VERSION );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andThrow( $database_exception );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Fetching database charset/collation failed.',
				Mockery::subset(
					[
						'operation' => 'get_charset_collate',
						'outcome' => 'failed',
						'exception' => $database_exception,
					],
				),
			);

		try {
			$this->create_runner( new NewMigration1( $this->database ) )->migrate();
			self::fail( 'Expected the migration runner to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame( 'Failed to fetch database charset and collation.', $exception->getMessage() );
			self::assertSame( $database_exception, $exception->getPrevious() );
		}
	}

	#[Test]
	public function migrate_logs_and_rethrows_migration_failure(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andReturn( self::INITIAL_VERSION );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Applying migration failed.',
				Mockery::on(
					static fn ( array $context ): bool => ( $context['migration_class'] ?? null ) === FailingMigration::class
						&& ( $context['migration_version'] ?? null ) === FailingMigration::version()
						&& ( $context['exception'] ?? null ) instanceof MigrationException,
				),
			);

		try {
			$this->create_runner( new FailingMigration( $this->database ) )->migrate();
			self::fail( 'Expected the migration runner to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame( 'Test migration failed.', $exception->getMessage() );
		}
	}

	#[Test]
	public function migrate_logs_and_throws_when_stored_version_cannot_be_updated(): void {

		$storage_exception = new FakeStorageException( 'Write failed.' );

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andReturn( self::INITIAL_VERSION );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->storage
			->shouldReceive( 'set' )
			->once()
			->with( self::OPTION_KEY, NewMigration1::version() )
			->andThrow( $storage_exception );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Updating stored DB version failed.',
				Mockery::subset(
					[
						'operation' => 'update_db_version',
						'outcome' => 'failed',
						'migration_class' => NewMigration1::class,
						'migration_version' => NewMigration1::version(),
					],
				),
			);

		try {
			$this->create_runner( new NewMigration1( $this->database ) )->migrate();
			self::fail( 'Expected the migration runner to throw.' );
		} catch ( MigrationException $exception ) {
			self::assertSame(
				sprintf(
					'Migration "%s" was applied, but updating stored DB version failed.',
					NewMigration1::version(),
				),
				$exception->getMessage(),
			);
			self::assertSame( $storage_exception, $exception->getPrevious() );
		}

		self::assertSame(
			[ [ NewMigration1::class, 'utf8mb4_unicode_ci' ] ],
			TestMigrationTrace::calls(),
		);
	}

	#[Test]
	public function migrate_throws_for_duplicate_migration_versions(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( self::OPTION_KEY )
			->andReturn( self::INITIAL_VERSION );

		$this->database
			->shouldReceive( 'get_charset_collate' )
			->once()
			->andReturn( 'utf8mb4_unicode_ci' );

		$this->expectException( MigrationException::class );
		$this->expectExceptionMessage(
			sprintf( 'Migration version must be unique. Given: %s.', NewMigration1::version() ),
		);

		$this->create_runner(
			new NewMigration1( $this->database ),
			new DuplicateVersionMigration( $this->database ),
		)->migrate();
	}

	private function create_runner( AbstractMigration ...$migrations ): MigrationRunner {

		return new MigrationRunner( $this->database, $this->storage, $this->logger, ...$migrations );
	}

	private function expect_migration_applied_log( string $migration_class, string $version ): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Applying migration succeeded.',
				Mockery::subset(
					[
						'migration_class' => $migration_class,
						'migration_version' => $version,
					],
				),
			);
	}

	private function expect_migrations_completed_log( int $applied, string $from, string $to, string $target, ): void {

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Running migrations completed.',
				Mockery::subset(
					[
						'applied' => $applied,
						'from_version' => $from,
						'to_version' => $to,
						'target_version' => $target,
					],
				),
			);
	}
}
