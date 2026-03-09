<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\Fixtures\InvokableListenerSpy;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use WP_REST_Server;

#[CoversClass( RestApiInitActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class RestApiInitActionHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'rest_api_init';

	private RestApiInitActionHookDispatcher $dispatcher;
	private WP_REST_Server $server;

	protected function setUp(): void {

		parent::setUp();

		$this->dispatcher = new RestApiInitActionHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$this->server = Mockery::mock( WP_REST_Server::class );
	}

	#[Test]
	public function register_registers_action_with_expected_hook_name(): void {

		$this->dispatcher->register();

		self::assertNotFalse( has_action( self::HOOK_NAME ) );
	}

	#[Test]
	public function handle_dispatches_to_listeners_with_valid_arguments(): void {

		$this->expect_failure_message_never();

		$listener = Mockery::mock( InvokableListenerSpy::class );

		$listener
			->shouldReceive( '__invoke' )
			->once()
			->ordered()
			->with( $this->server );

		$this->dispatcher->attach( $listener );

		$callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->dispatcher->register( ... ),
		);

		$callback( $this->server );
	}

	#[Test]
	public function handle_logs_and_returns_when_server_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestApiInitActionHookDispatcher::class,
			'server',
		);

		$dispatcher = new RestApiInitActionHookDispatcher( $logger );

		$callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$callback( 'invalid-server' );
	}

	#[Test]
	public function handle_sets_failure_message_and_returns_when_listener_throws(): void {

		$this->expect_failure_message_once();

		$throwing_listener = Mockery::mock( InvokableListenerSpy::class );
		$throwing_listener
			->shouldReceive( '__invoke' )
			->once()
			->ordered()
			->andThrow( new RuntimeException( 'Boom' ) );

		$listener_after = Mockery::mock( InvokableListenerSpy::class );
		$listener_after->shouldReceive( '__invoke' )->never();

		$this->dispatcher->attach( $throwing_listener );
		$this->dispatcher->attach( $listener_after );

		$callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->dispatcher->register( ... ),
		);

		$callback( $this->server );
	}
}
