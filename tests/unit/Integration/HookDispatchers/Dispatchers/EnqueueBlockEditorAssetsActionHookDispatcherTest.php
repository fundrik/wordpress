<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use ArrayObject;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( EnqueueBlockEditorAssetsActionHookDispatcher::class )]
#[UsesClass( Logger::class )]
#[UsesClass( HookDispatcherLogger::class )]
final class EnqueueBlockEditorAssetsActionHookDispatcherTest extends WordPressTestCase {

	private const string HOOK_NAME = 'enqueue_block_editor_assets';

	private EnqueueBlockEditorAssetsActionHookDispatcher $dispatcher;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->dispatcher = new EnqueueBlockEditorAssetsActionHookDispatcher(
			new HookDispatcherLogger( Mockery::mock( LoggerInterface::class ) ),
		);
	}

	#[Test]
	public function register_registers_enqueue_block_editor_assets_action_callback(): void {

		Actions\expectAdded( self::HOOK_NAME )
			->once()
			->with( [ $this->dispatcher, 'dispatch' ], 10, 1 );

		$this->dispatcher->register();
	}

	#[Test]
	public function dispatch_calls_attached_listeners_in_order(): void {

		$calls = new ArrayObject();

		$this->dispatcher->attach(
			static function () use ( $calls ): void {

				$calls[] = 'first';
			},
		);
		$this->dispatcher->attach(
			static function () use ( $calls ): void {

				$calls[] = 'second';
			},
		);

		$this->dispatcher->dispatch();

		self::assertSame( [ 'first', 'second' ], $calls->getArrayCopy() );
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

		$this->dispatcher->dispatch();

		self::assertCount( 0, $second_listener_calls );
	}
}
