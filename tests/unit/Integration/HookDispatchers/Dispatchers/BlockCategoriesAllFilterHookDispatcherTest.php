<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\BlockCategoriesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use stdClass;
use WP_Block_Editor_Context;

#[CoversClass( BlockCategoriesAllFilterHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class BlockCategoriesAllFilterHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'block_categories_all';

	private WP_Block_Editor_Context $editor_context;

	protected function setUp(): void {

		parent::setUp();

		$this->editor_context = Mockery::mock( WP_Block_Editor_Context::class );
	}

	#[Test]
	public function register_registers_filter(): void {

		$dispatcher = new BlockCategoriesAllFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->register();

		self::assertNotFalse( has_filter( self::HOOK_NAME ) );
	}

	#[Test]
	public function handle_dispatches_to_listeners_and_returns_modified_value(): void {

		$this->expect_failure_message_never();

		$dispatcher = new BlockCategoriesAllFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->attach(
			static function ( array $categories, WP_Block_Editor_Context $context ): array {

				$categories[] = [
					'slug' => 'fundrik',
					'title' => 'Fundrik',
				];

				return $categories;
			},
		);

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback(
			[
				[
					'slug' => 'widgets',
					'title' => 'Widgets',
				],
			],
			$this->editor_context,
		);

		self::assertSame(
			[
				[
					'slug' => 'widgets',
					'title' => 'Widgets',
				],
				[
					'slug' => 'fundrik',
					'title' => 'Fundrik',
				],
			],
			$returned,
		);
	}

	#[Test]
	public function handle_logs_and_returns_original_when_categories_are_invalid(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			BlockCategoriesAllFilterHookDispatcher::class,
			'categories',
		);

		$dispatcher = new BlockCategoriesAllFilterHookDispatcher( $logger );

		$original = 'invalid';

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
			BlockCategoriesAllFilterHookDispatcher::class,
			'editor_context',
		);

		$dispatcher = new BlockCategoriesAllFilterHookDispatcher( $logger );

		$original = [
			[
				'slug' => 'widgets',
				'title' => 'Widgets',
			],
		];

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

		$dispatcher = new BlockCategoriesAllFilterHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);

		$dispatcher->attach(
			static function (): never {
				throw new RuntimeException( 'Boom' );
			},
		);

		$original = [
			[
				'slug' => 'widgets',
				'title' => 'Widgets',
			],
		];

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, $this->editor_context );

		self::assertSame( $original, $returned );
	}

	#[Test]
	public function handle_returns_original_when_listener_returns_invalid_categories(): void {

		$this->expect_failure_message_once();

		$logger = $this->create_logger_expect_invalid_input_error(
			self::HOOK_NAME,
			BlockCategoriesAllFilterHookDispatcher::class,
			'categories',
		);
		$dispatcher = new BlockCategoriesAllFilterHookDispatcher( $logger );

		$dispatcher->attach(
			static fn (): mixed => [ new stdClass() ],
		);

		$original = [
			[
				'slug' => 'widgets',
				'title' => 'Widgets',
			],
		];

		$callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$dispatcher->register( ... ),
		);

		$returned = $callback( $original, $this->editor_context );

		self::assertSame( $original, $returned );
	}
}
