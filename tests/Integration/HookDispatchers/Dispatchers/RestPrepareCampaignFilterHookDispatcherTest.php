<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

#[CoversClass( RestPrepareCampaignFilterHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class RestPrepareCampaignFilterHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'rest_prepare_' . CampaignPostTypeConfig::ID;

	private WP_Post&MockInterface $post;

	private WP_REST_Request&MockInterface $request;

	protected function setUp(): void {

		parent::setUp();

		$this->post = Mockery::mock( WP_Post::class );
		$this->request = Mockery::mock( WP_REST_Request::class );
	}

	#[Test]
	public function register_registers_filter(): void {

		$dispatcher = new RestPrepareCampaignFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->register();

		self::assertSame(
			10,
			has_filter( self::HOOK_NAME, $dispatcher->handle( ... ) ),
		);
	}

	#[Test]
	public function handle_dispatches_to_listeners_and_returns_modified_response(): void {

		$this->expect_failure_message_never();

		$dispatcher = new RestPrepareCampaignFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$response = Mockery::mock( WP_REST_Response::class );
		$changed = Mockery::mock( WP_REST_Response::class );

		$dispatcher->attach(
			// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			static fn ( WP_REST_Response $r, WP_Post $p, WP_REST_Request $q ): WP_REST_Response => $changed,
		);

		$returned = $dispatcher->handle( $response, $this->post, $this->request );

		self::assertSame( $changed, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_response_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPrepareCampaignFilterHookDispatcher::class,
			'response',
		);

		$dispatcher = new RestPrepareCampaignFilterHookDispatcher( $logger );

		$original = 'invalid-response';

		$returned = $dispatcher->handle( $original, $this->post, $this->request );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_post_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPrepareCampaignFilterHookDispatcher::class,
			'post',
		);

		$dispatcher = new RestPrepareCampaignFilterHookDispatcher( $logger );

		$response = Mockery::mock( WP_REST_Response::class );
		$original = $response;

		$returned = $dispatcher->handle( $response, 'invalid-post', $this->request );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_request_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPrepareCampaignFilterHookDispatcher::class,
			'request',
		);

		$dispatcher = new RestPrepareCampaignFilterHookDispatcher( $logger );

		$response = Mockery::mock( WP_REST_Response::class );
		$original = $response;

		$returned = $dispatcher->handle( $response, $this->post, 'invalid-request' );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_returns_invalid_response(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPrepareCampaignFilterHookDispatcher::class,
			'response',
		);

		$dispatcher = new RestPrepareCampaignFilterHookDispatcher( $logger );

		$dispatcher->attach(
			static fn (): mixed => 'not-a-response',
		);

		$response = Mockery::mock( WP_REST_Response::class );

		$returned = $dispatcher->handle( $response, $this->post, $this->request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_throws(): void {

		$this->expect_failure_message_once();

		$dispatcher = new RestPrepareCampaignFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->attach(
			static function (): never {
				throw new RuntimeException( 'Boom' );
			},
		);

		$response = Mockery::mock( WP_REST_Response::class );

		$returned = $dispatcher->handle( $response, $this->post, $this->request );

		self::assertSame( $response, $returned );
	}
}
