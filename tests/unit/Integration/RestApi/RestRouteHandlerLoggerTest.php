<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi;

use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( RestRouteHandlerLogger::class )]
final class RestRouteHandlerLoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private RestRouteHandlerLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new RestRouteHandlerLogger( $this->psr_logger );
	}

	#[Test]
	public function it_throws_when_logging_without_setting_handler_class(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'REST route handler class must be set before logging. Given: unset.' );

		$this->logger->log_create_donation_failed( 33, 1_250, new RuntimeException( 'DB failed.' ) );
	}

	#[Test]
	public function it_logs_create_donation_failure_with_structured_context(): void {

		$this->logger->set_rest_route_handler_class( 'MyRestHandler' );
		$exception = new RuntimeException( 'DB failed.' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Creating donation from REST request failed.',
				Mockery::subset(
					[
						'service_class' => 'MyRestHandler',
						'logger_class' => RestRouteHandlerLogger::class,
						'component' => 'rest_route_handlers',
						'layer' => 'integration',
						'system' => 'wordpress',
						'operation' => 'create_donation',
						'outcome' => 'failed',
						'campaign_id' => 33,
						'amount_minor' => 1_250,
						'exception' => $exception,
					],
				),
			);

		$this->logger->log_create_donation_failed( 33, 1_250, $exception );
	}
}
