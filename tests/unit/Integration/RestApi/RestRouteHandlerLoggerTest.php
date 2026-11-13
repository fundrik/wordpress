<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( RestRouteHandlerLogger::class )]
#[UsesClass( Logger::class )]
final class RestRouteHandlerLoggerTest extends MockeryTestCase {

	private const string HANDLER_CLASS = 'Fundrik\Example\RestRouteHandler';

	private RestRouteHandlerLogger $logger;
	private LoggerInterface&MockInterface $psr_logger;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new RestRouteHandlerLogger( $this->psr_logger );
	}

	#[Test]
	public function set_rest_route_handler_class_adds_handler_context_to_log_entries(): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Handling REST request.',
				[
					'service_class' => self::HANDLER_CLASS,
					'logger_class' => RestRouteHandlerLogger::class,
					'component' => 'rest_route_handlers',
					'layer' => 'integration',
					'system' => 'wordpress',
					'operation' => 'handle_request',
				],
			);

		$this->logger->set_rest_route_handler_class( self::HANDLER_CLASS );
		$this->logger->log_debug( 'Handling REST request.', [ 'operation' => 'handle_request' ] );
	}

	#[Test]
	public function logging_throws_when_rest_route_handler_class_is_not_set(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'REST route handler class must be set before logging. Given: unset.' );

		$this->logger->log_info( 'Handling REST request.' );
	}
}
