<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\ActionPostDeletedEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\DeletePostActionBridge;
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

#[CoversClass( DeletePostActionBridge::class )]
#[UsesClass( ActionPostDeletedEvent::class )]
#[UsesClass( BridgeLogger::class )]
#[UsesClass( InvalidBridgeArgumentException::class )]
final class DeletePostActionBridgeTest extends WordPressTestCase {

	private WordPressContextInterface&MockInterface $context;
	private InfrastructureEventDispatcherInterface&MockInterface $dispatcher;

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	private DeletePostActionBridge $bridge;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->dispatcher = Mockery::mock( InfrastructureEventDispatcherInterface::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );

		$this->bridge = new DeletePostActionBridge( $this->context, $this->dispatcher, $this->logger );
	}

	#[Test]
	public function register_registers_action(): void {

		$this->bridge->register();

		self::assertSame( 10, has_action( 'delete_post', $this->bridge->handle( ... ) ) );
	}

	#[Test]
	public function handle_dispatches_event(): void {

		$post_id = 123;
		$post = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $post_id, $post ): bool {

						if ( ! $event instanceof ActionPostDeletedEvent ) {
							return false;
						}

						return $event->post_id === $post_id
							&& $event->post === $post
							&& $event->context === $this->context;
					},
				),
			);

		$this->bridge->handle( $post_id, $post );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_id_is_invalid(): void {

		$post_id = '123';
		$post = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $post_id argument in \'delete_post\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'post_id',
						'invoked' => false,
					],
				),
			);

		$this->bridge->handle( $post_id, $post );
	}

	#[Test]
	public function handle_logs_and_returns_when_post_is_invalid(): void {

		$post_id = 123;
		$post = 'not-a-post-object';

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $post argument in \'delete_post\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'post',
						'invoked' => false,
					],
				),
			);

		$this->bridge->handle( $post_id, $post );
	}

	#[Test]
	public function handle_logs_and_rethrows_when_dispatch_fails(): void {

		$post_id = 123;
		$post = Mockery::mock( WP_Post::class );
		$post->post_type = 'post';

		$e = new RuntimeException( 'Dispatch failed' );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( ActionPostDeletedEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				"Bridge dispatch failed for hook 'delete_post'.",
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

		$this->bridge->handle( $post_id, $post );
	}
}
