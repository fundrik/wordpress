<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\InvokableListenerSpy;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use WP_Post;
use WP_REST_Request;

#[CoversClass( RestAfterInsertCampaignActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class RestAfterInsertCampaignActionHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'rest_after_insert_' . CampaignPostTypeConfig::ID;

	private RestAfterInsertCampaignActionHookDispatcher $dispatcher;

	private WP_Post $post;

	private WP_REST_Request $request;

	protected function setUp(): void {

		parent::setUp();

		$this->dispatcher = new RestAfterInsertCampaignActionHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$this->post = Mockery::mock( WP_Post::class );
		$this->request = Mockery::mock( WP_REST_Request::class );
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
			->with( $this->post, $this->request, true );

		$this->dispatcher->attach( $listener );

		$callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->dispatcher->register( ... ),
		);

		$callback( $this->post, $this->request, true );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestAfterInsertCampaignActionHookDispatcher::class,
			'post',
		);

		$dispatcher = new RestAfterInsertCampaignActionHookDispatcher( $logger );

		$callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$callback( 'invalid-post', $this->request, true );
	}

	#[Test]
	public function handle_logs_and_returns_when_request_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestAfterInsertCampaignActionHookDispatcher::class,
			'request',
		);

		$dispatcher = new RestAfterInsertCampaignActionHookDispatcher( $logger );

		$callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$callback( $this->post, 'invalid-request', true );
	}

	#[Test]
	public function handle_logs_and_returns_when_creating_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestAfterInsertCampaignActionHookDispatcher::class,
			'creating',
		);

		$dispatcher = new RestAfterInsertCampaignActionHookDispatcher( $logger );

		$callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$callback( $this->post, $this->request, 'not-bool' );
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

		$callback( $this->post, $this->request, true );
	}
}
