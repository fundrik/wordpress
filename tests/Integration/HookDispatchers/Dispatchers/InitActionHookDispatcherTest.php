<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Fixtures\InvokableListenerSpy;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

#[CoversClass( InitActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
final class InitActionHookDispatcherTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'init';

	private InitActionHookDispatcher $dispatcher;

	protected function setUp(): void {

		parent::setUp();

		$this->dispatcher = new InitActionHookDispatcher(
			$this->create_logger_expect_no_errors(),
		);
	}

	#[Test]
	public function register_registers_action(): void {

		$this->dispatcher->register();

		self::assertSame(
			10,
			has_action( self::HOOK_NAME, $this->dispatcher->handle( ... ) ),
		);
	}

	#[Test]
	public function handle_calls_all_listeners(): void {

		$this->expect_failure_message_never();

		$listener1 = Mockery::mock( InvokableListenerSpy::class );
		$listener1->shouldReceive( '__invoke' )->once()->ordered();

		$listener2 = Mockery::mock( InvokableListenerSpy::class );
		$listener2->shouldReceive( '__invoke' )->once()->ordered();

		$this->dispatcher->attach( $listener1 );
		$this->dispatcher->attach( $listener2 );

		$this->dispatcher->handle();
	}

	#[Test]
	public function handle_sets_failure_message_and_stops_when_listener_throws(): void {

		$this->expect_failure_message_once();

		$throwing_listener = Mockery::mock( InvokableListenerSpy::class );
		$throwing_listener
			->shouldReceive( '__invoke' )
			->once()
			->ordered()
			->andThrow( new RuntimeException( 'Boom' ) );

		$listener_after = Mockery::mock( InvokableListenerSpy::class );
		$listener_after->shouldReceive( '__invoke' )->never();

		$this->dispatcher->attach( $throwing_listener );
		$this->dispatcher->attach( $listener_after );

		$this->dispatcher->handle();
	}
}
