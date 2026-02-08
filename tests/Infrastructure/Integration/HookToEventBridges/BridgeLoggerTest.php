<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookToEventBridges;

use Fundrik\WordPress\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( BridgeLogger::class )]
#[UsesClass( InvalidBridgeArgumentException::class )]
final class BridgeLoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );
	}

	#[Test]
	public function it_throws_when_logging_without_context(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage(
			'BridgeLogger context is not set. Call set_hook_name() and set_bridge_class() before logging.',
		);

		$this->logger->log_registered();
	}

	#[Test]
	public function it_logs_registered(): void {

		$this->logger->set_hook_name( 'init' );
		$this->logger->set_bridge_class( 'MyBridge' );

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Hook bridge registered.',
				Mockery::subset(
					[
						'logger_class' => BridgeLogger::class,
						'component' => 'hook_bridges',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'hook_name' => 'init',
						'bridge_class' => 'MyBridge',
						'operation' => 'register_hook_bridge',
						'outcome' => 'registered',
					],
				),
			);

		$this->logger->log_registered();
	}

	#[Test]
	public function it_logs_invalid_input(): void {

		$this->logger->set_hook_name( 'delete_post' );
		$this->logger->set_bridge_class( 'MyBridge' );

		$e = InvalidBridgeArgumentException::create( argument: 'post_id', hook: 'delete_post' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				$e->getMessage(),
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'post_id',
						'invoked' => false,
					],
				),
			);

		$this->logger->log_invalid_input( $e );
	}

	#[Test]
	public function it_logs_dispatch_failed(): void {

		$this->logger->set_hook_name( 'init' );
		$this->logger->set_bridge_class( 'MyBridge' );

		$e = new RuntimeException( 'Boom' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				"Bridge dispatch failed for hook 'init'.",
				Mockery::subset(
					[
						'operation' => 'dispatch_hook_bridge',
						'outcome' => 'error',
						'invoked' => true,
						'exception' => $e,
					],
				),
			);

		$this->logger->log_dispatch_failed( $e );
	}

	#[Test]
	public function it_logs_handled_with_extra_context(): void {

		$this->logger->set_hook_name( 'allowed_block_types_all' );
		$this->logger->set_bridge_class( 'MyBridge' );

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Hook bridge handled.',
				Mockery::subset(
					[
						'operation' => 'handle_hook_bridge',
						'outcome' => 'changed',
						'invoked' => true,
						'returned_type' => 'array',
						'returned_count' => 2,
					],
				),
			);

		$this->logger->log_handled(
			'changed',
			[
				'returned_type' => 'array',
				'returned_count' => 2,
			],
		);
	}
}
