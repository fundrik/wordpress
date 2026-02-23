<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Migrations;

use Fundrik\WordPress\Infrastructure\DatabaseException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationException;
use Fundrik\WordPress\Infrastructure\Migrations\MigrationRunnerLogger;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( MigrationRunnerLogger::class )]
final class MigrationRunnerLoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private MigrationRunnerLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new MigrationRunnerLogger( $this->psr_logger );
	}

	#[Test]
	public function it_logs_charset_collate_failed_as_error(): void {

		$e = new DatabaseException( 'No charset' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Fetching database charset/collation failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
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

		$this->logger->log_charset_collate_failed( $e );
	}

	#[Test]
	public function it_logs_migration_applied_as_debug_with_class_and_version(): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
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
						'migration_class' => 'Fundrik\\Example\\Migration',
						'migration_version' => '2025_08_04_01',
					],
				),
			);

		$this->logger->log_migration_applied( 'Fundrik\\Example\\Migration', '2025_08_04_01' );
	}

	#[Test]
	public function it_logs_db_version_update_failed_as_error_with_class_and_version(): void {

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Updating stored DB version failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'component' => 'migrations',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'update_db_version',
						'outcome' => 'failed',
						'migration_class' => 'Fundrik\\Example\\Migration',
						'migration_version' => '2025_08_04_01',
					],
				),
			);

		$this->logger->log_db_version_update_failed( 'Fundrik\\Example\\Migration', '2025_08_04_01' );
	}

	#[Test]
	public function it_logs_migration_failed_as_error_with_exception(): void {

		$e = new MigrationException( 'Boom' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Applying migration failed.',
				Mockery::subset(
					[
						'service_class' => MigrationRunnerPort::class,
						'logger_class' => MigrationRunnerLogger::class,
						'component' => 'migrations',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'apply_migration',
						'outcome' => 'failed',
						'migration_class' => 'Fundrik\\Example\\Migration',
						'migration_version' => '2025_08_04_01',
						'exception' => $e,
					],
				),
			);

		$this->logger->log_migration_failed( 'Fundrik\\Example\\Migration', '2025_08_04_01', $e );
	}

	#[Test]
	public function it_logs_migrations_completed_as_info_with_skipped_outcome_when_applied_is_zero(): void {

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
						'outcome' => 'skipped',
						'applied' => 0,
						'from_version' => '2400_01_12_01',
						'to_version' => '2400_01_12_01',
						'target_version' => '2400_01_12_01',
					],
				),
			);

		$this->logger->log_migrations_completed( 0, '2400_01_12_01', '2400_01_12_01', '2400_01_12_01' );
	}

	#[Test]
	public function it_logs_migrations_completed_as_info_with_applied_outcome_when_applied_is_positive(): void {

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

		$this->logger->log_migrations_completed( 2, '2025_06_14_00', '2400_01_12_01', '2400_01_12_01' );
	}
}
