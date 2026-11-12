<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\BlockCategoriesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpBlockEditorContext;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use WP_Block_Editor_Context;

#[CoversClass( BlockCategoriesAllFilterHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class BlockCategoriesAllFilterHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'block_categories_all';

	private LoggerInterface&MockInterface $psr_logger;
	private BlockCategoriesAllFilterHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new BlockCategoriesAllFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_block_categories_all_filter_callback(): void {

		Filters\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 2 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_passes_each_listener_the_previous_result(): void {

		$context = new WpBlockEditorContext();
		$text_category = [
			'slug' => 'text',
			'title' => 'Text',
		];
		$media_category = [
			'slug' => 'media',
			'title' => 'Media',
		];
		$fundrik_category = [
			'slug' => 'fundrik',
			'title' => 'Fundrik',
		];

		$this->dispatcher->attach(
			static function (
				array $categories,
				WP_Block_Editor_Context $received_context,
			) use (
				$context,
				$text_category,
				$media_category,
			): array {

				self::assertSame( [ $text_category ], $categories );
				self::assertSame( $context, $received_context );

				return [ ...$categories, $media_category ];
			},
		);
		$this->dispatcher->attach(
			static function (
				array $categories,
				WP_Block_Editor_Context $received_context,
			) use (
				$context,
				$text_category,
				$media_category,
				$fundrik_category,
			): array {

				self::assertSame( [ $text_category, $media_category ], $categories );
				self::assertSame( $context, $received_context );

				return [ ...$categories, $fundrik_category ];
			},
		);

		$result = $this->dispatcher->dispatch( [ $text_category ], $context );

		self::assertSame( [ $text_category, $media_category, $fundrik_category ], $result );
	}

	#[Test]
	#[DataProvider( 'invalid_categories_cases' )]
	public function dispatch_returns_original_value_when_categories_are_invalid( mixed $categories ): void {

		$this->expect_invalid_argument( 'categories' );

		$result = $this->dispatcher->dispatch( $categories, new WpBlockEditorContext() );

		self::assertSame( $categories, $result );
	}

	#[Test]
	public function dispatch_returns_original_value_when_editor_context_is_invalid(): void {

		$categories = [
			[
				'slug' => 'text',
				'title' => 'Text',
			],
		];

		$this->expect_invalid_argument( 'editor_context' );

		$result = $this->dispatcher->dispatch( $categories, new stdClass() );

		self::assertSame( $categories, $result );
	}

	#[Test]
	public function dispatch_returns_original_value_when_listener_result_is_invalid(): void {

		$categories = [
			[
				'slug' => 'text',
				'title' => 'Text',
			],
		];

		$this->expect_invalid_argument( 'categories' );

		$this->dispatcher->attach( static fn (): string => 'invalid' );

		$result = $this->dispatcher->dispatch( $categories, new WpBlockEditorContext() );

		self::assertSame( $categories, $result );
	}

	#[Test]
	public function dispatch_sets_failure_message_and_returns_original_value_after_listener_exception(): void {

		$categories = [
			[
				'slug' => 'text',
				'title' => 'Text',
			],
		];

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Listener failed.' );

		$this->dispatcher->attach(
			static function (): never {

				throw new RuntimeException( 'Listener failed.' );
			},
		);
		$this->dispatcher->attach(
			static fn (): array => [ [ 'slug' => 'should-not-be-returned' ] ],
		);

		$result = $this->dispatcher->dispatch( $categories, new WpBlockEditorContext() );

		self::assertSame( $categories, $result );
	}

	/**
	 * Returns invalid block category values.
	 *
	 * @return iterable<string, array{mixed}> Invalid values.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public static function invalid_categories_cases(): iterable {

		yield 'categories are not an array' => [ 'invalid' ];
		yield 'category is not an array' => [ [ [ 'slug' => 'text' ], 'invalid' ] ];
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
