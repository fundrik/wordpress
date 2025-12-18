<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Kernel\Ports\Out\EventListenerRegistrarPort;
use RuntimeException;

/**
 * Registers all event listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class EventListenerRegistrar implements EventListenerRegistrarPort {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EventListenerRegistry $registry Provides the map of events to their listeners.
	 * @param EventDispatcherInterface $dispatcher Registers event listeners.
	 */
	public function __construct(
		private EventListenerRegistry $registry,
		private EventDispatcherInterface $dispatcher,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers all declared event listeners.
	 *
	 * @since 1.0.0
	 *
	 * @throws RuntimeException Thrown when the event or listener class does not implement the required interface.
	 */
	public function register_all(): void {

		foreach ( $this->registry->get_event_listener_map() as $event => $listener ) {

			if ( ! is_subclass_of( $event, EventInterface::class ) ) {

				throw new RuntimeException(
					sprintf(
						'Event must implement %s. Given: %s.',
						EventInterface::class,
						$event,
					),
				);
			}

			if ( ! is_subclass_of( $listener, EventListenerInterface::class ) ) {

				throw new RuntimeException(
					sprintf(
						'Event listener must implement %s. Given: %s.',
						EventListenerInterface::class,
						$listener,
					),
				);
			}

			$this->dispatcher->listen( $event, $listener );
		}
	}
	// phpcs:enable
}
