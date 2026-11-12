<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
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
use WP_Error;
use WP_REST_Request;

#[CoversClass( RestPreInsertCampaignFilterHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
#[UsesClass( CampaignPostTypeConfig::class )]
final class RestPreInsertCampaignFilterHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'rest_pre_insert_fundrik_campaign';

	private LoggerInterface&MockInterface $psr_logger;
	private RestPreInsertCampaignFilterHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new RestPreInsertCampaignFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_rest_pre_insert_campaign_filter_callback(): void {

		Filters\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 2 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_passes_each_listener_the_previous_result(): void {

		$prepared_post = new stdClass();
		$first_result = new stdClass();
		$final_result = new WP_Error( 'invalid_campaign', 'Campaign is invalid.' );
		$request = new WP_REST_Request();

		$this->dispatcher->attach(
			static function (
				stdClass $received_post,
				WP_REST_Request $received_request,
			) use (
				$prepared_post,
				$first_result,
				$request,
			): stdClass {

				self::assertSame( $prepared_post, $received_post );
				self::assertSame( $request, $received_request );

				return $first_result;
			},
		);
		$this->dispatcher->attach(
			static function (
				stdClass $received_post,
				WP_REST_Request $received_request,
			) use (
				$first_result,
				$final_result,
				$request,
			): WP_Error {

				self::assertSame( $first_result, $received_post );
				self::assertSame( $request, $received_request );

				return $final_result;
			},
		);

		$result = $this->dispatcher->dispatch( $prepared_post, $request );

		self::assertSame( $final_result, $result );
	}

	#[Test]
	public function dispatch_returns_original_value_when_prepared_post_is_invalid(): void {

		$prepared_post = [ 'post_title' => 'Campaign' ];

		$this->expect_invalid_argument( 'prepared_post' );

		$result = $this->dispatcher->dispatch( $prepared_post, new WP_REST_Request() );

		self::assertSame( $prepared_post, $result );
	}

	#[Test]
	public function dispatch_returns_original_post_when_request_is_invalid(): void {

		$prepared_post = new stdClass();

		$this->expect_invalid_argument( 'request' );

		$result = $this->dispatcher->dispatch( $prepared_post, new stdClass() );

		self::assertSame( $prepared_post, $result );
	}

	#[Test]
	public function dispatch_returns_original_post_when_listener_result_is_invalid(): void {

		$prepared_post = new stdClass();

		$this->expect_invalid_argument( 'returned' );

		$this->dispatcher->attach( static fn (): string => 'invalid' );

		$result = $this->dispatcher->dispatch( $prepared_post, new WP_REST_Request() );

		self::assertSame( $prepared_post, $result );
	}

	#[Test]
	public function dispatch_sets_failure_message_and_returns_original_post_after_listener_exception(): void {

		$prepared_post = new stdClass();

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Listener failed.' );

		$this->dispatcher->attach(
			static function (): never {

				throw new RuntimeException( 'Listener failed.' );
			},
		);
		$this->dispatcher->attach( static fn (): stdClass => new stdClass() );

		$result = $this->dispatcher->dispatch( $prepared_post, new WP_REST_Request() );

		self::assertSame( $prepared_post, $result );
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
