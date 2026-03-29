<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPostDispatchFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

#[CoversClass( RestPostDispatchFilterHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class RestPostDispatchFilterHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'rest_post_dispatch';

	private WP_REST_Request&MockInterface $request;
	private WP_REST_Server&MockInterface $server;

	protected function setUp(): void {

		parent::setUp();

		$this->request = Mockery::mock( WP_REST_Request::class );
		$this->server = Mockery::mock( WP_REST_Server::class );
	}

	#[Test]
	public function register_registers_filter(): void {

		$dispatcher = new RestPostDispatchFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->register();

		self::assertNotFalse( has_filter( self::HOOK_NAME ) );
	}

	#[Test]
	public function handle_dispatches_to_listeners_and_returns_modified_response(): void {

		$this->expect_failure_message_never();

		$dispatcher = new RestPostDispatchFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$response = Mockery::mock( WP_REST_Response::class );
		$changed = Mockery::mock( WP_REST_Response::class );

		$dispatcher->attach(
			// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			static fn ( WP_REST_Response $r, WP_REST_Server $s, WP_REST_Request $q ): WP_REST_Response => $changed,
		);

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $response, $this->server, $this->request );

		self::assertSame( $changed, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_response_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPostDispatchFilterHookDispatcher::class,
			'response',
		);

		$dispatcher = new RestPostDispatchFilterHookDispatcher( $logger );

		$original = 'invalid-response';

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, $this->server, $this->request );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_server_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPostDispatchFilterHookDispatcher::class,
			'server',
		);

		$dispatcher = new RestPostDispatchFilterHookDispatcher( $logger );

		$response = Mockery::mock( WP_REST_Response::class );

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $response, 'invalid-server', $this->request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_request_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPostDispatchFilterHookDispatcher::class,
			'request',
		);

		$dispatcher = new RestPostDispatchFilterHookDispatcher( $logger );

		$response = Mockery::mock( WP_REST_Response::class );

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $response, $this->server, 'invalid-request' );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_returns_invalid_response(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPostDispatchFilterHookDispatcher::class,
			'response',
		);

		$dispatcher = new RestPostDispatchFilterHookDispatcher( $logger );

		$dispatcher->attach(
			static fn (): mixed => 'not-a-response',
		);

		$response = Mockery::mock( WP_REST_Response::class );

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $response, $this->server, $this->request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_throws(): void {

		$this->expect_failure_message_once();

		$dispatcher = new RestPostDispatchFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->attach(
			static function (): never {
				throw new RuntimeException( 'Boom' );
			},
		);

		$response = Mockery::mock( WP_REST_Response::class );

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $response, $this->server, $this->request );

		self::assertSame( $response, $returned );
	}
}
