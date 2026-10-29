<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunnerLogger;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( MigrationRunnerLogger::class )]
#[UsesClass( Logger::class )]
final class MigrationRunnerLoggerTest extends MockeryTestCase {

	private const string MIGRATION_CLASS = 'Fundrik\Example\Migration';
	private const string MIGRATION_VERSION = '2026_01_01_00';

	private LoggerInterface&MockInterface $psr_logger;
	private MigrationRunnerLogger $logger;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new MigrationRunnerLogger( $this->psr_logger );
	}

	#[Test]
	public function log_charset_collate_failed_logs_error_with_exception(): void {

		$exception = new FakeDatabaseException( 'Charset lookup failed.' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Fetching database charset/collation failed.',
				$this->expected_context(
					[
						'operation' => 'get_charset_collate',
						'outcome' => 'failed',
						'exception' => $exception,
					],
				),
			);

		$this->logger->log_charset_collate_failed( $exception );
	}

	#[Test]
	public function log_migration_applied_logs_debug_with_migration_details(): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Applying migration succeeded.',
				$this->expected_context(
					[
						'operation' => 'apply_migration',
						'outcome' => 'applied',
						'migration_class' => self::MIGRATION_CLASS,
						'migration_version' => self::MIGRATION_VERSION,
					],
				),
			);

		$this->logger->log_migration_applied( self::MIGRATION_CLASS, self::MIGRATION_VERSION );
	}

	#[Test]
	public function log_db_version_update_failed_logs_error_with_migration_details(): void {

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Updating stored DB version failed.',
				$this->expected_context(
					[
						'operation' => 'update_db_version',
						'outcome' => 'failed',
						'migration_class' => self::MIGRATION_CLASS,
						'migration_version' => self::MIGRATION_VERSION,
					],
				),
			);

		$this->logger->log_db_version_update_failed( self::MIGRATION_CLASS, self::MIGRATION_VERSION );
	}

	#[Test]
	public function log_migration_failed_logs_error_with_migration_details_and_exception(): void {

		$exception = new MigrationException( 'Migration failed.' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Applying migration failed.',
				$this->expected_context(
					[
						'operation' => 'apply_migration',
						'outcome' => 'failed',
						'migration_class' => self::MIGRATION_CLASS,
						'migration_version' => self::MIGRATION_VERSION,
						'exception' => $exception,
					],
				),
			);

		$this->logger->log_migration_failed( self::MIGRATION_CLASS, self::MIGRATION_VERSION, $exception );
	}

	#[Test]
	#[DataProvider( 'completion_cases' )]
	public function log_migrations_completed_logs_info_with_completion_details(
		int $applied,
		string $from,
		string $to,
		string $target,
		string $outcome,
	): void {

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Running migrations completed.',
				$this->expected_context(
					[
						'operation' => 'migrate',
						'outcome' => $outcome,
						'applied' => $applied,
						'from_version' => $from,
						'to_version' => $to,
						'target_version' => $target,
					],
				),
			);

		$this->logger->log_migrations_completed( $applied, $from, $to, $target );
	}

	public static function completion_cases(): iterable {

		yield 'no migrations applied' => [
			0,
			'2026_01_01_00',
			'2026_01_01_00',
			'2026_01_01_00',
			'skipped',
		];

		yield 'migrations applied' => [
			2,
			'2025_01_01_00',
			'2026_01_01_01',
			'2026_01_01_01',
			'applied',
		];
	}

	private function expected_context( array $extra ): array {

		return [
			'service_class' => MigrationRunnerPort::class,
			'logger_class' => MigrationRunnerLogger::class,
			'component' => 'migrations',
			'layer' => 'infrastructure',
			'system' => 'wordpress',
		] + $extra;
	}
}
