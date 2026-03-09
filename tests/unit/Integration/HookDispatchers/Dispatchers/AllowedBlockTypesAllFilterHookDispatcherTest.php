<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use stdClass;
use WP_Block_Editor_Context;

#[CoversClass( AllowedBlockTypesAllFilterHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class AllowedBlockTypesAllFilterHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'allowed_block_types_all';

	private WP_Block_Editor_Context&MockInterface $editor_context;

	protected function setUp(): void {

		parent::setUp();

		$this->editor_context = Mockery::mock( WP_Block_Editor_Context::class );
	}

	#[Test]
	public function register_registers_filter(): void {

		$dispatcher = new AllowedBlockTypesAllFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->register();

		self::assertNotFalse( has_filter( self::HOOK_NAME ) );
	}

	#[Test]
	public function handle_dispatches_to_listeners_and_returns_modified_value(): void {

		$this->expect_failure_message_never();

		$dispatcher = new AllowedBlockTypesAllFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->attach(
			// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			static function ( array|bool $allowed, WP_Block_Editor_Context $context ): array|bool {

				if ( $allowed === true ) {
					return [ 'core/paragraph' ];
				}

				return $allowed;
			},
		);

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( true, $this->editor_context );

		self::assertSame( [ 'core/paragraph' ], $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_allowed_is_invalid_scalar(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			AllowedBlockTypesAllFilterHookDispatcher::class,
			'allowed',
		);

		$dispatcher = new AllowedBlockTypesAllFilterHookDispatcher( $logger );

		$original = 'invalid';

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, $this->editor_context );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_allowed_is_invalid_array(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			AllowedBlockTypesAllFilterHookDispatcher::class,
			'allowed',
		);

		$dispatcher = new AllowedBlockTypesAllFilterHookDispatcher( $logger );

		$original = [ new stdClass() ];

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, $this->editor_context );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_editor_context_is_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			AllowedBlockTypesAllFilterHookDispatcher::class,
			'editor_context',
		);

		$dispatcher = new AllowedBlockTypesAllFilterHookDispatcher( $logger );

		$original = true;

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, 'invalid-context' );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_throws(): void {

		$this->expect_failure_message_once();

		$dispatcher = new AllowedBlockTypesAllFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->attach(
			static function (): never {
				throw new RuntimeException( 'Boom' );
			},
		);

		$original = true;

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, $this->editor_context );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_returns_invalid_allowed(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			AllowedBlockTypesAllFilterHookDispatcher::class,
			'allowed',
		);

		$dispatcher = new AllowedBlockTypesAllFilterHookDispatcher( $logger );

		$dispatcher->attach(
			static fn (): mixed => 'not-valid',
		);

		$original = true;

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, $this->editor_context );

		self::assertSame( $original, $returned );
	}
}
