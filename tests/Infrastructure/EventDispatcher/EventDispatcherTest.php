<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcher;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Illuminate\Contracts\Events\Dispatcher as IlluminateDispatcher;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( EventDispatcher::class )]
final class EventDispatcherTest extends MockeryTestCase {

	private InfrastructureEventDispatcherInterface $dispatcher;

	private IlluminateDispatcher&MockInterface $inner;

	protected function setUp(): void {

		parent::setUp();

		$this->inner = Mockery::mock( IlluminateDispatcher::class );
		$this->dispatcher = new EventDispatcher( $this->inner );
	}

	#[Test]
	public function dispatch_delegates_to_inner_dispatcher(): void {

		$event = new class() implements InfrastructureEventInterface {}; // phpcs:ignore

		$this->inner
			->shouldReceive( 'dispatch' )
			->once()
			->with( $this->identicalTo( $event ) );

		$this->dispatcher->dispatch( $event );
	}

	#[Test]
	public function listen_delegates_to_inner_dispatcher(): void {

		$this->inner
			->shouldReceive( 'listen' )
			->once()
			->with( 'MyEventClass', 'MyListenerClass' );

		$this->dispatcher->listen( 'MyEventClass', 'MyListenerClass' );
	}
}
