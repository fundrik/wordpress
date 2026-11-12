<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use ArrayObject;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpPost;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestRequest;
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

#[CoversClass( RestAfterInsertCampaignActionHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
#[UsesClass( CampaignPostTypeConfig::class )]
final class RestAfterInsertCampaignActionHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'rest_after_insert_fundrik_campaign';

	private LoggerInterface&MockInterface $psr_logger;
	private RestAfterInsertCampaignActionHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new RestAfterInsertCampaignActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_rest_after_insert_campaign_action_callback(): void {

		Actions\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 3 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_calls_attached_listeners_in_order_with_validated_arguments(): void {

		$post = new WpPost( post_type: CampaignPostTypeConfig::ID, id: 42 );
		$request = new WpRestRequest( method: 'POST' );
		$calls = new ArrayObject();

		$this->dispatcher->attach(
			static function (
				WP_Post $received_post,
				WP_REST_Request $received_request,
				bool $creating,
			) use (
				$post,
				$request,
				$calls,
			): void {

				self::assertSame( $post, $received_post );
				self::assertSame( $request, $received_request );
				self::assertFalse( $creating );
				$calls[] = 'first';
			},
		);
		$this->dispatcher->attach(
			static function (
				WP_Post $received_post,
				WP_REST_Request $received_request,
				bool $creating,
			) use (
				$post,
				$request,
				$calls,
			): void {

				self::assertSame( $post, $received_post );
				self::assertSame( $request, $received_request );
				self::assertFalse( $creating );
				$calls[] = 'second';
			},
		);

		$this->dispatcher->dispatch( $post, $request, false );

		self::assertSame( [ 'first', 'second' ], $calls->getArrayCopy() );
	}

	#[Test]
	public function dispatch_rejects_non_post_value(): void {

		$this->expect_invalid_argument( 'post' );

		$this->dispatcher->dispatch( new stdClass(), new WpRestRequest(), false );
	}

	#[Test]
	public function dispatch_rejects_non_request_value(): void {

		$this->expect_invalid_argument( 'request' );

		$this->dispatcher->dispatch( new WpPost(), new stdClass(), false );
	}

	#[Test]
	public function dispatch_rejects_non_boolean_creating_value(): void {

		$this->expect_invalid_argument( 'creating' );

		$this->dispatcher->dispatch( new WpPost(), new WpRestRequest(), 1 );
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

		$this->dispatcher->dispatch( new WpPost(), new WpRestRequest(), true );

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
