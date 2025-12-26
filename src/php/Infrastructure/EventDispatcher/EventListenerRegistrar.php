<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Kernel\Ports\EventListenerRegistrarPort;
use RuntimeException;

/**
 * Registers all infrastructure event listeners.
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
	 * @param InfrastructureEventDispatcherInterface $dispatcher Registers event listeners.
	 */
	public function __construct(
		private EventListenerRegistry $registry,
		private InfrastructureEventDispatcherInterface $dispatcher,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers all declared infrastructure event listeners.
	 *
	 * @since 1.0.0
	 *
	 * @throws RuntimeException When the registry contains an invalid event or listener class.
	 */
	public function register_all(): void {

		foreach ( $this->registry->get_event_listener_map() as $event => $listener ) {

			if ( ! is_subclass_of( $event, InfrastructureEventInterface::class ) ) {

				throw new RuntimeException(
					sprintf(
						'Event class must implement %s. Given: %s.',
						InfrastructureEventInterface::class,
						$event,
					),
				);
			}

			if ( ! is_subclass_of( $listener, InfrastructureEventListenerInterface::class ) ) {

				throw new RuntimeException(
					sprintf(
						'Event listener class must implement %s. Given: %s.',
						InfrastructureEventListenerInterface::class,
						$listener,
					),
				);
			}

			$this->dispatcher->listen( $event, $listener );
		}
	}
	// phpcs:enable
}
