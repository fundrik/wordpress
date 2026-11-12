<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
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

#[CoversClass( AllowedBlockTypesAllFilterHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InvalidHookDispatcherArgumentException::class )]
final class AllowedBlockTypesAllFilterHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'allowed_block_types_all';

	private LoggerInterface&MockInterface $psr_logger;
	private AllowedBlockTypesAllFilterHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->dispatcher = new AllowedBlockTypesAllFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
	}

	#[Test]
	public function register_registers_allowed_block_types_all_filter_callback(): void {

		Filters\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 2 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_passes_each_listener_the_previous_result(): void {

		$context = new WpBlockEditorContext();

		$this->dispatcher->attach(
			static function ( array $allowed, WP_Block_Editor_Context $received_context ) use ( $context ): array {

				self::assertSame( [ 'core/paragraph' ], $allowed );
				self::assertSame( $context, $received_context );

				return [ ...$allowed, 'core/heading' ];
			},
		);
		$this->dispatcher->attach(
			static function ( array $allowed, WP_Block_Editor_Context $received_context ) use ( $context ): array {

				self::assertSame( [ 'core/paragraph', 'core/heading' ], $allowed );
				self::assertSame( $context, $received_context );

				return [ ...$allowed, 'fundrik/campaign' ];
			},
		);

		$result = $this->dispatcher->dispatch( [ 'core/paragraph' ], $context );

		self::assertSame( [ 'core/paragraph', 'core/heading', 'fundrik/campaign' ], $result );
	}

	#[Test]
	public function dispatch_accepts_boolean_allowed_value(): void {

		self::assertFalse( $this->dispatcher->dispatch( false, new WpBlockEditorContext() ) );
	}

	#[Test]
	#[DataProvider( 'invalid_allowed_cases' )]
	public function dispatch_returns_original_value_when_allowed_is_invalid( mixed $allowed ): void {

		$this->expect_invalid_argument( 'allowed' );

		$result = $this->dispatcher->dispatch( $allowed, new WpBlockEditorContext() );

		self::assertSame( $allowed, $result );
	}

	#[Test]
	public function dispatch_returns_original_value_when_editor_context_is_invalid(): void {

		$this->expect_invalid_argument( 'editor_context' );

		$result = $this->dispatcher->dispatch( [ 'core/paragraph' ], new stdClass() );

		self::assertSame( [ 'core/paragraph' ], $result );
	}

	#[Test]
	public function dispatch_returns_original_value_when_listener_result_is_invalid(): void {

		$this->expect_invalid_argument( 'allowed' );

		$this->dispatcher->attach( static fn (): int => 42 );

		$result = $this->dispatcher->dispatch( [ 'core/paragraph' ], new WpBlockEditorContext() );

		self::assertSame( [ 'core/paragraph' ], $result );
	}

	#[Test]
	public function dispatch_sets_failure_message_and_returns_original_value_after_listener_exception(): void {

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Listener failed.' );

		$this->dispatcher->attach(
			static function (): never {

				throw new RuntimeException( 'Listener failed.' );
			},
		);
		$this->dispatcher->attach( static fn (): array => [ 'should-not-be-returned' ] );

		$result = $this->dispatcher->dispatch( [ 'core/paragraph' ], new WpBlockEditorContext() );

		self::assertSame( [ 'core/paragraph' ], $result );
	}

	/**
	 * Returns invalid allowed block type values.
	 *
	 * @return iterable<string, array{mixed}> Invalid values.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public static function invalid_allowed_cases(): iterable {

		yield 'unsupported value type' => [ 'core/paragraph' ];
		yield 'non-string block type' => [ [ 'core/paragraph', 42 ] ];
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
