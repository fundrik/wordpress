<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventDispatcher;

use Illuminate\Contracts\Events\Dispatcher as LaravelDispatcherInterface;

/**
 * Dispatches infrastructure events and registers infrastructure event listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class EventDispatcher implements InfrastructureEventDispatcherInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LaravelDispatcherInterface $inner Dispatches events using Laravel's event system.
	 */
	public function __construct(
		private LaravelDispatcherInterface $inner,
	) {}

	/**
	 * Dispatches an event to all registered listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param InfrastructureEventInterface $event The event to dispatch.
	 */
	public function dispatch( InfrastructureEventInterface $event ): void {

		$this->inner->dispatch( $event );
	}

	/**
	 * Registers a listener for the given event class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $event_class The class name of the event to listen for.
	 * @param string $listener_class The class name of the listener that handles the event.
	 *
	 * @phpstan-param class-string<InfrastructureEventInterface> $event_class
	 * @phpstan-param class-string<InfrastructureEventListenerInterface> $listener_class
	 */
	public function listen( string $event_class, string $listener_class ): void {

		$this->inner->listen( $event_class, $listener_class );
	}
}
