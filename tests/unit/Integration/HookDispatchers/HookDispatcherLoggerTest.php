<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( HookDispatcherLogger::class )]
#[UsesClass( Logger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class HookDispatcherLoggerTest extends MockeryTestCase {

	private const string HOOK_NAME = 'allowed_block_types_all';
	private const string DISPATCHER_CLASS = 'Fundrik\Example\AllowedBlocksDispatcher';

	private LoggerInterface&MockInterface $psr_logger;
	private HookDispatcherLogger $logger;
	private InvalidHookDispatcherArgumentException $validation_exception;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new HookDispatcherLogger( $this->psr_logger );
		$this->validation_exception = InvalidHookDispatcherArgumentException::create(
			argument: 'allowed',
			hook: self::HOOK_NAME,
		);
	}

	#[Test]
	public function log_invalid_input_throws_when_hook_name_is_not_set(): void {

		$this->logger->set_hook_dispatcher_class( self::DISPATCHER_CLASS );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Hook dispatcher logger context must be set before logging. Given: unset.' );

		$this->logger->log_invalid_input( $this->validation_exception );
	}

	#[Test]
	public function log_invalid_input_throws_when_dispatcher_class_is_not_set(): void {

		$this->logger->set_hook_name( self::HOOK_NAME );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Hook dispatcher logger context must be set before logging. Given: unset.' );

		$this->logger->log_invalid_input( $this->validation_exception );
	}

	#[Test]
	public function log_invalid_input_logs_error_with_structured_context(): void {

		$this->logger->set_hook_name( self::HOOK_NAME );
		$this->logger->set_hook_dispatcher_class( self::DISPATCHER_CLASS );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				$this->validation_exception->getMessage(),
				[
					'service_class' => self::DISPATCHER_CLASS,
					'logger_class' => HookDispatcherLogger::class,
					'component' => 'hook_dispatchers',
					'layer' => 'integration',
					'system' => 'wordpress',
					'hook_name' => self::HOOK_NAME,
					'operation' => 'validate',
					'outcome' => 'invalid',
					'invalid_argument' => 'allowed',
					'invoked' => false,
				],
			);

		$this->logger->log_invalid_input( $this->validation_exception );
	}
}
