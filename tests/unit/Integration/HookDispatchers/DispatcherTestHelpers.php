<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use ArrayObject;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
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

	// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	/**
	 * Registers an action hook and captures the callback passed to add_action().
	 */
	private function register_and_capture_action_callback( string $hook_name, callable $register ): callable {

		$state = new ArrayObject(
			[
				'callback' => null,
			],
		);

		Actions\expectAdded( $hook_name )
			->once()
			->whenHappen(
				static function (
					callable $registered_callback,
					int $priority = 10,
					int $accepted_args = 1,
				) use ( $state ): void {

					$state['callback'] = $registered_callback;
				},
			);

		$register();

		self::assertIsCallable( $state['callback'] );

		return $state['callback'];
	}

	/**
	 * Registers a filter hook and captures the callback passed to add_filter().
	 */
	private function register_and_capture_filter_callback( string $hook_name, callable $register ): callable {

		$state = new ArrayObject(
			[
				'callback' => null,
			],
		);

		Filters\expectAdded( $hook_name )
			->once()
			->whenHappen(
				static function (
					callable $registered_callback,
					int $priority = 10,
					int $accepted_args = 1,
				) use ( $state ): void {

					$state['callback'] = $registered_callback;
				},
			);

		$register();

		self::assertIsCallable( $state['callback'] );

		return $state['callback'];
	}
	// phpcs:enable
}
