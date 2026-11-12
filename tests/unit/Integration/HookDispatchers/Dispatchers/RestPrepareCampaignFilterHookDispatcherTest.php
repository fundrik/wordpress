<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpPost;
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
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

#[CoversClass( RestPrepareCampaignFilterHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
#[UsesClass( CampaignPostTypeConfig::class )]
final class RestPrepareCampaignFilterHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'rest_prepare_fundrik_campaign';

	private LoggerInterface&MockInterface $psr_logger;
	private RestPrepareCampaignFilterHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new RestPrepareCampaignFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_rest_prepare_campaign_filter_callback(): void {

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
		$post = new WpPost( post_type: CampaignPostTypeConfig::ID, id: 42 );
		$request = new WP_REST_Request();

		$this->dispatcher->attach(
			static function (
				WP_REST_Response $response,
				WP_Post $received_post,
				WP_REST_Request $received_request,
			) use (
				$initial_response,
				$first_response,
				$post,
				$request,
			): WP_REST_Response {

				self::assertSame( $initial_response, $response );
				self::assertSame( $post, $received_post );
				self::assertSame( $request, $received_request );

				return $first_response;
			},
		);
		$this->dispatcher->attach(
			static function (
				WP_REST_Response $response,
				WP_Post $received_post,
				WP_REST_Request $received_request,
			) use (
				$first_response,
				$second_response,
				$post,
				$request,
			): WP_REST_Response {

				self::assertSame( $first_response, $response );
				self::assertSame( $post, $received_post );
				self::assertSame( $request, $received_request );

				return $second_response;
			},
		);

		$result = $this->dispatcher->dispatch( $initial_response, $post, $request );

		self::assertSame( $second_response, $result );
	}

	#[Test]
	public function dispatch_returns_original_value_when_response_is_invalid(): void {

		$response = new stdClass();

		$this->expect_invalid_argument( 'response' );

		$result = $this->dispatcher->dispatch( $response, new WpPost(), new WP_REST_Request() );

		self::assertSame( $response, $result );
	}

	#[Test]
	public function dispatch_returns_original_response_when_post_is_invalid(): void {

		$response = new WP_REST_Response();

		$this->expect_invalid_argument( 'post' );

		$result = $this->dispatcher->dispatch( $response, new stdClass(), new WP_REST_Request() );

		self::assertSame( $response, $result );
	}

	#[Test]
	public function dispatch_returns_original_response_when_request_is_invalid(): void {

		$response = new WP_REST_Response();

		$this->expect_invalid_argument( 'request' );

		$result = $this->dispatcher->dispatch( $response, new WpPost(), new stdClass() );

		self::assertSame( $response, $result );
	}

	#[Test]
	public function dispatch_returns_original_response_when_listener_result_is_invalid(): void {

		$response = new WP_REST_Response();

		$this->expect_invalid_argument( 'response' );

		$this->dispatcher->attach( static fn (): stdClass => new stdClass() );

		$result = $this->dispatcher->dispatch( $response, new WpPost(), new WP_REST_Request() );

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

		$result = $this->dispatcher->dispatch( $response, new WpPost(), new WP_REST_Request() );

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
