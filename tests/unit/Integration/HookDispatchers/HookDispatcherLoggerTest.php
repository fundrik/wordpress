<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class HookDispatcherLoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private HookDispatcherLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new HookDispatcherLogger( $this->psr_logger );
	}

	#[Test]
	public function it_throws_when_logging_without_context(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage(
			'Hook dispatcher logger context must be set before logging. Given: unset.',
		);

		$this->logger->log_invalid_input(
			InvalidHookDispatcherArgumentException::create( argument: 'allowed', hook: 'init' ),
		);
	}

	#[Test]
	public function it_logs_invalid_input(): void {

		$this->logger->set_hook_name( 'init' );
		$this->logger->set_hook_dispatcher_class( 'MyDispatcher' );

		$e = InvalidHookDispatcherArgumentException::create( argument: 'allowed', hook: 'init' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				$e->getMessage(),
				Mockery::subset(
					[
						'logger_class' => HookDispatcherLogger::class,
						'component' => 'hook_dispatchers',
						'layer' => 'integration',
						'system' => 'wordpress',
						'hook_name' => 'init',
						'service_class' => 'MyDispatcher',
						'operation' => 'validate',
						'outcome' => 'invalid',
						'invalid_argument' => 'allowed',
						'invoked' => false,
					],
				),
			);

		$this->logger->log_invalid_input( $e );
	}
}
