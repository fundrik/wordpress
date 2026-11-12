<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPostDispatchFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
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
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

#[CoversClass( RestPostDispatchFilterHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class RestPostDispatchFilterHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'rest_post_dispatch';

	private LoggerInterface&MockInterface $psr_logger;
	private RestPostDispatchFilterHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new RestPostDispatchFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_rest_post_dispatch_filter_callback(): void {

		Filters\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 3 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_passes_each_listener_the_previous_response(): void {

		$initial_response = new WP_REST_Response( [ 'stage' => 'initial' ] );
		$first_response = new WP_REST_Response( [ 'stage' => 'first' ] );
		$second_response = new WP_REST_Response( [ 'stage' => 'second' ] );
		$server = new WP_REST_Server();
		$request = new WP_REST_Request();

		$this->dispatcher->attach(
			static function (
				WP_REST_Response $response,
				WP_REST_Server $received_server,
				WP_REST_Request $received_request,
			) use (
				$initial_response,
				$first_response,
				$server,
				$request,
			): WP_REST_Response {

				self::assertSame( $initial_response, $response );
				self::assertSame( $server, $received_server );
				self::assertSame( $request, $received_request );

				return $first_response;
			},
		);
		$this->dispatcher->attach(
			static function (
				WP_REST_Response $response,
				WP_REST_Server $received_server,
				WP_REST_Request $received_request,
			) use (
				$first_response,
				$second_response,
				$server,
				$request,
			): WP_REST_Response {

				self::assertSame( $first_response, $response );
				self::assertSame( $server, $received_server );
				self::assertSame( $request, $received_request );

				return $second_response;
			},
		);

		$result = $this->dispatcher->dispatch( $initial_response, $server, $request );

		self::assertSame( $second_response, $result );
	}

	#[Test]
	public function dispatch_returns_original_value_when_response_is_invalid(): void {

		$response = new stdClass();

		$this->expect_invalid_argument( 'response' );

		$result = $this->dispatcher->dispatch( $response, new WP_REST_Server(), new WP_REST_Request() );

		self::assertSame( $response, $result );
	}

	#[Test]
	public function dispatch_returns_original_response_when_server_is_invalid(): void {

		$response = new WP_REST_Response();

		$this->expect_invalid_argument( 'server' );

		$result = $this->dispatcher->dispatch( $response, new stdClass(), new WP_REST_Request() );

		self::assertSame( $response, $result );
	}

	#[Test]
	public function dispatch_returns_original_response_when_request_is_invalid(): void {

		$response = new WP_REST_Response();

		$this->expect_invalid_argument( 'request' );

		$result = $this->dispatcher->dispatch( $response, new WP_REST_Server(), new stdClass() );

		self::assertSame( $response, $result );
	}

	#[Test]
	public function dispatch_returns_original_response_when_listener_result_is_invalid(): void {

		$response = new WP_REST_Response();

		$this->expect_invalid_argument( 'response' );

		$this->dispatcher->attach( static fn (): stdClass => new stdClass() );

		$result = $this->dispatcher->dispatch( $response, new WP_REST_Server(), new WP_REST_Request() );

		self::assertSame( $response, $result );
	}

	#[Test]
	public function dispatch_sets_failure_message_and_returns_original_response_after_listener_exception(): void {

		$response = new WP_REST_Response();

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Listener failed.' );

		$this->dispatcher->attach(
			static function (): never {

				throw new RuntimeException( 'Listener failed.' );
			},
		);
		$this->dispatcher->attach( static fn (): WP_REST_Response => new WP_REST_Response() );

		$result = $this->dispatcher->dispatch( $response, new WP_REST_Server(), new WP_REST_Request() );

		self::assertSame( $response, $result );
	}

	private function expect_invalid_argument( string $argument ): void {

		$message = sprintf( 'Invalid $%s argument in %s hook.', $argument, self::HOOK_NAME );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				$message,
				Mockery::on(
					static fn ( array $context ): bool => ( $context['invalid_argument'] ?? null ) === $argument,
				),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( $message );
	}
}
