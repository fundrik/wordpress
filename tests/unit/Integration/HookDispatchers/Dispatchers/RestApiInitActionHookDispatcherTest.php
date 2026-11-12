<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use ArrayObject;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestServer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use WP_REST_Server;

#[CoversClass( RestApiInitActionHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class RestApiInitActionHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'rest_api_init';

	private LoggerInterface&MockInterface $psr_logger;
	private RestApiInitActionHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new RestApiInitActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_rest_api_init_action_callback(): void {

		Actions\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 1 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_calls_attached_listeners_in_order_with_validated_server(): void {

		$server = new WpRestServer();
		$calls = new ArrayObject();

		$this->dispatcher->attach(
			static function ( WP_REST_Server $received_server ) use ( $server, $calls ): void {

				self::assertSame( $server, $received_server );
				$calls[] = 'first';
			},
		);
		$this->dispatcher->attach(
			static function ( WP_REST_Server $received_server ) use ( $server, $calls ): void {

				self::assertSame( $server, $received_server );
				$calls[] = 'second';
			},
		);

		$this->dispatcher->dispatch( $server );

		self::assertSame( [ 'first', 'second' ], $calls->getArrayCopy() );
	}

	#[Test]
	public function dispatch_rejects_non_rest_server_value(): void {

		$message = 'Invalid $server argument in rest_api_init hook.';

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				$message,
				Mockery::on(
					static fn ( array $context ): bool => ( $context['invalid_argument'] ?? null ) === 'server',
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( $message );

		$this->dispatcher->dispatch( new stdClass() );
	}

	#[Test]
	public function dispatch_sets_failure_message_and_stops_after_listener_exception(): void {

		$second_listener_calls = new ArrayObject();

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Listener failed.' );

		$this->dispatcher->attach(
			static function (): never {

				throw new RuntimeException( 'Listener failed.' );
			},
		);
		$this->dispatcher->attach(
			static function () use ( $second_listener_calls ): void {

				$second_listener_calls[] = true;
			},
		);

		$this->dispatcher->dispatch( new WpRestServer() );

		self::assertCount( 0, $second_listener_calls );
	}
}
