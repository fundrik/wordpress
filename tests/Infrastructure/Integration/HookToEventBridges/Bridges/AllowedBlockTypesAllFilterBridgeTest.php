<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\AllowedBlockTypesAllFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use WP_Block_Editor_Context;

#[CoversClass( AllowedBlockTypesAllFilterBridge::class )]
#[UsesClass( FilterAllowedBlockTypesEvent::class )]
#[UsesClass( BridgeLogger::class )]
#[UsesClass( InvalidBridgeArgumentException::class )]
final class AllowedBlockTypesAllFilterBridgeTest extends WordPressTestCase {

	private WordPressContextInterface&MockInterface $context;
	private InfrastructureEventDispatcherInterface&MockInterface $dispatcher;

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	private AllowedBlockTypesAllFilterBridge $bridge;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->dispatcher = Mockery::mock( InfrastructureEventDispatcherInterface::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );

		$this->bridge = new AllowedBlockTypesAllFilterBridge( $this->context, $this->dispatcher, $this->logger );
	}

	#[Test]
	public function register_registers_filter(): void {

		$this->bridge->register();

		self::assertSame( 10, has_filter( 'allowed_block_types_all', $this->bridge->handle( ... ) ) );
	}

	#[Test]
	public function handle_dispatches_event_for_bool_allowed_and_returns_original_when_unchanged(): void {

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $editor_context ): bool {

						if ( ! $event instanceof FilterAllowedBlockTypesEvent ) {
							return false;
						}

						return $event->allowed === true
							&& $event->editor_context === $editor_context
							&& $event->context === $this->context;
					},
				),
			);

		$returned = $this->bridge->handle( true, $editor_context );

		self::assertTrue( $returned );
	}

	#[Test]
	public function handle_dispatches_event_for_array_allowed_and_returns_modified_value_from_event(): void {

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ) use ( $editor_context ): bool {

						if ( ! $event instanceof FilterAllowedBlockTypesEvent ) {
							return false;
						}

						if ( $event->allowed !== [ 'core/paragraph', 'core/image' ] ) {
							return false;
						}

						if ( $event->editor_context !== $editor_context || $event->context !== $this->context ) {
							return false;
						}

						// Simulate listeners changing the allowed blocks.
						$event->allowed = [ 'core/paragraph' ];

						return true;
					},
				),
			);

		$returned = $this->bridge->handle( [ 'core/paragraph', 'core/image' ], $editor_context );

		self::assertSame( [ 'core/paragraph' ], $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_allowed_is_invalid_array(): void {

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$allowed = [ new stdClass() ];

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $allowed argument in \'allowed_block_types_all\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'allowed',
						'invoked' => false,
					],
				),
			);

		$returned = $this->bridge->handle( $allowed, $editor_context );

		self::assertSame( $allowed, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_allowed_is_invalid_scalar(): void {

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$allowed = 'not-valid';

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $allowed argument in \'allowed_block_types_all\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'allowed',
						'invoked' => false,
					],
				),
			);

		$returned = $this->bridge->handle( $allowed, $editor_context );

		self::assertSame( $allowed, $returned );
	}

	#[Test]
	public function handle_logs_and_returns_original_when_editor_context_is_invalid(): void {

		$allowed = true;
		$editor_context = 'invalid-context';

		$this->dispatcher
			->shouldNotReceive( 'dispatch' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Invalid $editor_context argument in \'allowed_block_types_all\' hook.',
				Mockery::subset(
					[
						'operation' => 'validate_hook_bridge',
						'outcome' => 'invalid',
						'invalid_argument' => 'editor_context',
						'invoked' => false,
					],
				),
			);

		$returned = $this->bridge->handle( $allowed, $editor_context );

		self::assertTrue( $returned );
	}

	#[Test]
	public function handle_logs_and_rethrows_when_dispatch_fails(): void {

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$allowed = true;

		$e = new RuntimeException( 'Dispatch failed' );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( FilterAllowedBlockTypesEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				"Bridge dispatch failed for hook 'allowed_block_types_all'.",
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

		$this->bridge->handle( $allowed, $editor_context );
	}
}
