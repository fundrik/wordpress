<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\InitActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( InitActionBridge::class )]
#[UsesClass( BridgeLogger::class )]
#[UsesClass( RegisterPostTypesEvent::class )]
#[UsesClass( RegisterBlocksEvent::class )]
final class InitActionBridgeTest extends WordPressTestCase {

	private WordPressContextInterface&MockInterface $context;
	private InfrastructureEventDispatcherInterface&MockInterface $dispatcher;

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	private InitActionBridge $bridge;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
		$this->dispatcher = Mockery::mock( InfrastructureEventDispatcherInterface::class );

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );

		$this->bridge = new InitActionBridge( $this->context, $this->dispatcher, $this->logger );
	}

	#[Test]
	public function register_registers_action(): void {

		$this->bridge->register();

		self::assertSame( 10, has_action( 'init', $this->bridge->handle( ... ) ) );
	}

	#[Test]
	public function handle_dispatches_both_events(): void {

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ): bool {

						if ( ! $event instanceof RegisterPostTypesEvent ) {
								return false;
						}

						return $event->context === $this->context;
					},
				),
			);

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with(
				Mockery::on(
					function ( object $event ): bool {

						if ( ! $event instanceof RegisterBlocksEvent ) {
								return false;
						}

						return $event->context === $this->context;
					},
				),
			);

		$this->bridge->handle();
	}

	#[Test]
	public function handle_logs_and_rethrows_when_first_dispatch_fails(): void {

		$e = new RuntimeException( 'First failed' );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( RegisterPostTypesEvent::class ) )
			->andThrow( $e );

		$this->dispatcher
			->shouldNotReceive( 'dispatch' )
			->with( Mockery::type( RegisterBlocksEvent::class ) );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				"Bridge dispatch failed for hook 'init'.",
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
		$this->expectExceptionMessage( 'First failed' );

		$this->bridge->handle();
	}

	#[Test]
	public function handle_logs_and_rethrows_when_second_dispatch_fails(): void {

		$e = new RuntimeException( 'Second failed' );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( RegisterPostTypesEvent::class ) );

		$this->dispatcher
			->shouldReceive( 'dispatch' )
			->once()
			->with( Mockery::type( RegisterBlocksEvent::class ) )
			->andThrow( $e );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				"Bridge dispatch failed for hook 'init'.",
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
		$this->expectExceptionMessage( 'Second failed' );

		$this->bridge->handle();
	}
}
