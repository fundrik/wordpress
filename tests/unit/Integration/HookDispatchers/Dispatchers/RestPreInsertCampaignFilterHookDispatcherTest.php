<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use stdClass;
use WP_Error;
use WP_REST_Request;

#[CoversClass( RestPreInsertCampaignFilterHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class RestPreInsertCampaignFilterHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'rest_pre_insert_' . CampaignPostTypeConfig::ID;

	#[Test]
	public function register_registers_filter(): void {

		$dispatcher = new RestPreInsertCampaignFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->register();

		self::assertNotFalse( has_filter( self::HOOK_NAME ) );
	}

	#[Test]
	public function handle_dispatches_to_listeners_and_returns_modified_stdclass(): void {

		$this->expect_failure_message_never();

		$dispatcher = new RestPreInsertCampaignFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$prepared_post = new stdClass();
		$request = new WP_REST_Request();

		$changed = new stdClass();

		$dispatcher->attach(
			// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			static fn ( stdClass $p, WP_REST_Request $r ): stdClass => $changed,
		);

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $prepared_post, $request );

		self::assertSame( $changed, $returned );
	}

	#[Test]
	public function handle_dispatches_to_listeners_and_returns_wp_error(): void {

		$this->expect_failure_message_never();

		$dispatcher = new RestPreInsertCampaignFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$prepared_post = new stdClass();
		$request = new WP_REST_Request();

		$changed = new WP_Error( 'fundrik_error', 'Boom' );

		$dispatcher->attach(
			// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			static fn ( stdClass $p, WP_REST_Request $r ): WP_Error => $changed,
		);

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $prepared_post, $request );

		self::assertSame( $changed, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_prepared_post_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPreInsertCampaignFilterHookDispatcher::class,
			'prepared_post',
		);

		$dispatcher = new RestPreInsertCampaignFilterHookDispatcher( $logger );

		$original = 'invalid-prepared-post';

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, new WP_REST_Request() );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_request_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPreInsertCampaignFilterHookDispatcher::class,
			'request',
		);

		$dispatcher = new RestPreInsertCampaignFilterHookDispatcher( $logger );

		$prepared_post = new stdClass();
		$original = $prepared_post;

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $prepared_post, 'invalid-request' );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_returns_invalid_value(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			RestPreInsertCampaignFilterHookDispatcher::class,
			'returned',
		);

		$dispatcher = new RestPreInsertCampaignFilterHookDispatcher( $logger );

		$dispatcher->attach(
			static fn (): mixed => 'not-a-valid-result',
		);

		$prepared_post = new stdClass();

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $prepared_post, new WP_REST_Request() );

		self::assertSame( $prepared_post, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_throws(): void {

		$this->expect_failure_message_once();

		$dispatcher = new RestPreInsertCampaignFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->attach(
			static function (): never {
				throw new RuntimeException( 'Boom' );
			},
		);

		$prepared_post = new stdClass();

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $prepared_post, new WP_REST_Request() );

		self::assertSame( $prepared_post, $returned );
	}
}
