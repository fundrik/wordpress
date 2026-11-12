<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use ArrayObject;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
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

#[CoversClass( DeletePostActionHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class DeletePostActionHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'delete_post';

	private LoggerInterface&MockInterface $psr_logger;
	private DeletePostActionHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new DeletePostActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_delete_post_action_callback(): void {

		Actions\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 2 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_calls_attached_listeners_in_order_with_validated_arguments(): void {

		$post = new WpPost( id: 42 );
		$calls = new ArrayObject();

		$this->dispatcher->attach(
			static function ( int $post_id, WP_Post $received_post ) use ( $post, $calls ): void {

				self::assertSame( 42, $post_id );
				self::assertSame( $post, $received_post );
				$calls[] = 'first';
			},
		);
		$this->dispatcher->attach(
			static function ( int $post_id, WP_Post $received_post ) use ( $post, $calls ): void {

				self::assertSame( 42, $post_id );
				self::assertSame( $post, $received_post );
				$calls[] = 'second';
			},
		);

		$this->dispatcher->dispatch( 42, $post );

		self::assertSame( [ 'first', 'second' ], $calls->getArrayCopy() );
	}

	#[Test]
	public function dispatch_rejects_non_integer_post_id(): void {

		$this->expect_invalid_argument( 'post_id' );

		$this->dispatcher->dispatch( '42', new WpPost( id: 42 ) );
	}

	#[Test]
	public function dispatch_rejects_non_post_value(): void {

		$this->expect_invalid_argument( 'post' );

		$this->dispatcher->dispatch( 42, new stdClass() );
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

		$this->dispatcher->dispatch( 42, new WpPost( id: 42 ) );

		self::assertCount( 0, $second_listener_calls );
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
