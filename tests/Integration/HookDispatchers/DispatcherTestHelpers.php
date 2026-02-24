<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Mockery;
use Psr\Log\LoggerInterface;

trait DispatcherTestHelpers {

	private function create_logger_expect_no_errors(): HookDispatcherLogger {

		$psr_logger = Mockery::mock( LoggerInterface::class );
		$psr_logger->shouldNotReceive( 'error' );

		return new HookDispatcherLogger( $psr_logger );
	}

	private function create_logger_expect_invalid_input_error(
		string $hook_name,
		string $dispatcher_class,
		string $argument,
	): HookDispatcherLogger {

		$psr_logger = Mockery::mock( LoggerInterface::class );

		$psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				Mockery::type( 'string' ),
				Mockery::subset(
					[
						'hook_name' => $hook_name,
						'service_class' => $dispatcher_class,
						'invalid_argument' => $argument,
						'operation' => 'validate',
						'outcome' => 'invalid',
						'invoked' => false,
					],
				),
			);

		return new HookDispatcherLogger( $psr_logger );
	}

	private function expect_failure_message_once(): void {

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( Mockery::type( 'string' ) );
	}

	private function expect_failure_message_never(): void {

		Functions\expect( 'fundrik_set_failure_message' )->never();
	}
}
