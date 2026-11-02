<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Tests\Fixtures\TestLogger;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( Logger::class )]
final class LoggerTest extends MockeryTestCase {

	private const string SERVICE_CLASS = 'Fundrik\Example\Service';

	private LoggerInterface&MockInterface $psr_logger;
	private TestLogger $logger;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new TestLogger( $this->psr_logger );
		$this->logger->set_service_class( self::SERVICE_CLASS );
	}

	#[Test]
	public function log_debug_logs_debug_entry_with_structured_context(): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Debug entry.',
				[
					'service_class' => self::SERVICE_CLASS,
					'logger_class' => TestLogger::class,
					'component' => 'tests',
					'layer' => 'infrastructure',
					'system' => 'wordpress',
					'scope' => 'fixture',
					'operation' => 'test',
				],
			);

		$this->logger->log_debug(
			'Debug entry.',
			[
				'operation' => 'test',
				'system' => 'overridden',
				'scope' => 'overridden',
			],
		);
	}

	#[Test]
	public function log_debug_throws_when_service_class_is_not_set(): void {

		$logger = new TestLogger( $this->psr_logger );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Service class must be set before logging. Given: unset.' );

		$logger->log_debug( 'Debug entry.' );
	}

	#[Test]
	public function log_info_logs_info_entry(): void {

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with( 'Info entry.', $this->expected_context() );

		$this->logger->log_info( 'Info entry.' );
	}

	#[Test]
	public function log_warning_logs_warning_entry(): void {

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with( 'Warning entry.', $this->expected_context() );

		$this->logger->log_warning( 'Warning entry.' );
	}

	#[Test]
	public function log_error_logs_error_entry(): void {

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with( 'Error entry.', $this->expected_context() );

		$this->logger->log_error( 'Error entry.' );
	}

	private function expected_context(): array {

		return [
			'service_class' => self::SERVICE_CLASS,
			'logger_class' => TestLogger::class,
			'component' => 'tests',
			'layer' => 'infrastructure',
			'system' => 'wordpress',
			'scope' => 'fixture',
		];
	}
}
