<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
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
use WP_Post;

#[CoversClass( DeletePostActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class DeletePostActionHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'delete_post';

	private DeletePostActionHookDispatcher $dispatcher;

	private WP_Post $post;

	protected function setUp(): void {

		parent::setUp();

		$this->dispatcher = new DeletePostActionHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$this->post = Mockery::mock( WP_Post::class );
	}

	#[Test]
	public function register_registers_action_with_expected_hook_name(): void {

		$this->dispatcher->register();

		self::assertSame(
			10,
			has_action( self::HOOK_NAME, $this->dispatcher->handle( ... ) ),
		);
	}

	#[Test]
	public function handle_dispatches_to_listeners_with_valid_arguments(): void {

		$this->expect_failure_message_never();

		$listener = Mockery::mock( InvokableListenerSpy::class );

		$listener
			->shouldReceive( '__invoke' )
			->once()
			->ordered()
			->with( 123, $this->post );

		$this->dispatcher->attach( $listener );

		$this->dispatcher->handle( 123, $this->post );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_id_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			DeletePostActionHookDispatcher::class,
			'post_id',
		);

		$dispatcher = new DeletePostActionHookDispatcher( $logger );

		$dispatcher->handle( 'invalid-id', $this->post );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			DeletePostActionHookDispatcher::class,
			'post',
		);

		$dispatcher = new DeletePostActionHookDispatcher( $logger );

		$dispatcher->handle( 123, 'invalid-post' );
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

		$this->dispatcher->handle( 123, $this->post );
	}
}
