<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\PostSavedEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\WpAfterInsertPostActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use WP_Post;

#[CoversClass( WpAfterInsertPostActionBridge::class )]
#[UsesClass( PostSavedEvent::class )]
#[UsesClass( BridgeLogger::class )]
#[UsesClass( InvalidBridgeArgumentException::class )]
final class WpAfterInsertPostActionBridgeTest extends WordPressTestCase {

	private WordPressContextInterface&MockInterface $context;
	private InfrastructureEventDispatcherInterface&MockInterface $dispatcher;

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	private WpAfterInsertPostActionBridge $bridge;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->dispatcher = Mockery::mock( InfrastructureEventDispatcherInterface::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );

		$this->bridge = new WpAfterInsertPostActionBridge( $this->context, $this->dispatcher, $this->logger );
	}

	#[Test]
	public function register_registers_action(): void {

		$this->bridge->register();

		self::assertSame( 10, has_action( 'wp_after_insert_post', $this->bridge->handle( ... ) ) );
	}

	#[Test]
	public function handle_dispatches_event_for_new_post(): void {

		$post_id = 123;

		$post = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$update = false;
		$post_before = null;

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $post_id, $post, $update, $post_before ): bool {

						if ( ! $event instanceof PostSavedEvent ) {
							return false;
						}

						return $event->post_id === $post_id
							&& $event->post === $post
							&& $event->update === $update
							&& $event->post_before === $post_before
							&& $event->context === $this->context;
					},
				),
			);

		$this->bridge->handle( $post_id, $post, $update, $post_before );
	}

	#[Test]
	public function handle_dispatches_event_for_updated_post_with_post_before(): void {

		$post_id = 456;

		$post = Mockery::mock( WP_Post::class );
		$post->post_type = 'fundrik_campaign';

		$update = true;

		$post_before = Mockery::mock( WP_Post::class );
		$post_before->post_type = 'fundrik_campaign';

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $post_id, $post, $update, $post_before ): bool {

						if ( ! $event instanceof PostSavedEvent ) {
							return false;
						}

						return $event->post_id === $post_id
							&& $event->post === $post
							&& $event->update === $update
							&& $event->post_before === $post_before
							&& $event->context === $this->context;
					},
				),
			);

		$this->bridge->handle( $post_id, $post, $update, $post_before );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_id_is_invalid(): void {

		$post_id = '123';

		$post = Mockery::mock( WP_Post::class );

		$update = false;
		$post_before = null;

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $post_id argument in \'wp_after_insert_post\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'post_id',
						'invoked' => false,
					],
				),
			);

		$this->bridge->handle( $post_id, $post, $update, $post_before );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_is_invalid(): void {

		$post_id = 123;
		$post = 'not-a-post-object';

		$update = false;
		$post_before = null;

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $post argument in \'wp_after_insert_post\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'post',
						'invoked' => false,
					],
				),
			);

		$this->bridge->handle( $post_id, $post, $update, $post_before );
	}

	#[Test]
	public function handle_logs_and_returns_when_update_is_invalid(): void {

		$post_id = 123;

		$post = Mockery::mock( WP_Post::class );

		$update = 'not-bool';
		$post_before = null;

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $update argument in \'wp_after_insert_post\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'update',
						'invoked' => false,
					],
				),
			);

		$this->bridge->handle( $post_id, $post, $update, $post_before );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_before_is_invalid(): void {

		$post_id = 123;

		$post = Mockery::mock( WP_Post::class );

		$update = true;
		$post_before = 'not-a-wp-post';

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $post_before argument in \'wp_after_insert_post\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'post_before',
						'invoked' => false,
					],
				),
			);

		$this->bridge->handle( $post_id, $post, $update, $post_before );
	}

	#[Test]
	public function handle_logs_and_rethrows_when_dispatch_fails(): void {

		$post_id = 123;

		$post = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$update = false;
		$post_before = null;

		$e = new RuntimeException( 'Dispatch failed' );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( PostSavedEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				"Bridge dispatch failed for hook 'wp_after_insert_post'.",
				Mockery::subset(
					[
						'operation' => 'dispatch_hook_bridge',
						'outcome' => 'error',
						'invoked' => true,
						'exception' => $e,
					],
				),
			);

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Dispatch failed' );

		$this->bridge->handle( $post_id, $post, $update, $post_before );
	}
}
