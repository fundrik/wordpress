<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Tests\Fixtures\DummyLogger;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( Logger::class )]
#[UsesClass( DummyLogger::class )]
final class LoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
	}

	#[Test]
	public function it_throws_when_logging_without_setting_service_class(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage(
			'Service class must be set before logging. Given: unset.',
		);

		$logger = new DummyLogger( $this->psr_logger );

		$logger->log_error( 'Error entry.' );
	}

	#[Test]
	public function it_logs_error_with_structured_context(): void {

		$logger = new DummyLogger( $this->psr_logger );

		$logger->set_service_class( 'MyService' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Error entry.',
				Mockery::subset(
					[
						'service_class' => 'MyService',
						'logger_class' => DummyLogger::class,
						'component' => 'tests',
						'layer' => 'tests',
						'system' => 'wordpress',
						'outcome' => 'failed',
					],
				),
			);

		$logger->log_error(
			'Error entry.',
			[
				'outcome' => 'failed',
			],
		);
	}
}
