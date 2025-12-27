<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerRegistrar;
use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerRegistry;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventListenerInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use stdClass;

#[CoversClass( EventListenerRegistrar::class )]
final class EventListenerRegistrarTest extends MockeryTestCase {

	private EventListenerRegistry&MockInterface $registry;
	private InfrastructureEventDispatcherInterface&MockInterface $dispatcher;

	private EventListenerRegistrar $registrar;

	protected function setUp(): void {

		parent::setUp();

		$this->registry = Mockery::mock( EventListenerRegistry::class );
		$this->dispatcher = Mockery::mock( InfrastructureEventDispatcherInterface::class );

		$this->registrar = new EventListenerRegistrar( $this->registry, $this->dispatcher );
	}

	#[Test]
	public function it_registers_all_event_listeners(): void {

		// phpcs:disable
		$event_1 = new class() implements InfrastructureEventInterface {};
		$event_2 = new class() implements InfrastructureEventInterface {};
		$event_3 = new class() implements InfrastructureEventInterface {};

		$listener_1 = new class() implements InfrastructureEventListenerInterface {};
		$listener_2 = new class() implements InfrastructureEventListenerInterface {};
		$listener_3 = new class() implements InfrastructureEventListenerInterface {};
		// phpcs:enable

		$event_1_class = $event_1::class;
		$event_2_class = $event_2::class;
		$event_3_class = $event_3::class;

		$listener_1_class = $listener_1::class;
		$listener_2_class = $listener_2::class;
		$listener_3_class = $listener_3::class;

		$this->registry
			->shouldReceive( 'get_event_listener_map' )
			->once()
			->andReturn(
				[
					$event_1_class => $listener_1_class,
					$event_2_class => $listener_2_class,
					$event_3_class => $listener_3_class,
				],
			);

		$this->dispatcher
			->shouldReceive( 'listen' )
			->once()
			->with( $event_1_class, $listener_1_class );

		$this->dispatcher
			->shouldReceive( 'listen' )
			->once()
			->with( $event_2_class, $listener_2_class );

		$this->dispatcher
			->shouldReceive( 'listen' )
			->once()
			->with( $event_3_class, $listener_3_class );

		$this->registrar->register_all();
	}

	#[Test]
	public function it_throws_when_registry_contains_invalid_event_class(): void {

		$invalid_event_class = stdClass::class;

		$valid_listener = new class() implements InfrastructureEventListenerInterface {}; // phpcs:ignore
		$valid_listener_class = $valid_listener::class;

		$this->registry
			->shouldReceive( 'get_event_listener_map' )
			->once()
			->andReturn(
				[
					$invalid_event_class => $valid_listener_class,
				],
			);

		$this->dispatcher->shouldNotReceive( 'listen' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Event class must implement %s. Given: %s.',
				InfrastructureEventInterface::class,
				$invalid_event_class,
			),
		);

		$this->registrar->register_all();
	}

	#[Test]
	public function it_throws_when_registry_contains_invalid_listener_class(): void {

		$valid_event = new class() implements InfrastructureEventInterface {}; // phpcs:ignore
		$valid_event_class = $valid_event::class;

		$invalid_listener_class = \stdClass::class;

		$this->registry
			->shouldReceive( 'get_event_listener_map' )
			->once()
			->andReturn(
				[
					$valid_event_class => $invalid_listener_class,
				],
			);

		$this->dispatcher->shouldNotReceive( 'listen' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Event listener class must implement %s. Given: %s.',
				InfrastructureEventListenerInterface::class,
				$invalid_listener_class,
			),
		);

		$this->registrar->register_all();
	}
}
