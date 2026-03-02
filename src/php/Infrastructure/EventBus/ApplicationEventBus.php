<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventBus;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Throwable;

/**
 * Orchestrates application event publishing through configured channels.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ApplicationEventBus implements ApplicationEventBusPort {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventPublisherPort $publisher Publishes events through a concrete integration channel.
	 */
	public function __construct(
		private ApplicationEventPublisherPort $publisher,
	) {}

	/**
	 * Publishes the given application event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event The application event to publish.
	 *
	 * @throws ApplicationEventBusException When publishing fails.
	 */
	public function publish( ApplicationEventInterface $event ): void {

		try {
			$this->publisher->publish( $event );
		} catch ( Throwable $e ) {

			throw new ApplicationEventBusException(
				sprintf(
					'Cannot publish application event. Given: %s.',
					$event::class,
				),
				previous: $e,
			);
		}
	}
}
