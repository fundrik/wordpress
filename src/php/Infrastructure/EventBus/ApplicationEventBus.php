<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventBus;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Override;
use Throwable;

/**
 * Dispatches application events to configured listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ApplicationEventBus implements ApplicationEventBusPort {

	/**
	 * Configured event listeners.
	 *
	 * @var list<ApplicationEventListenerInterface>
	 */
	private array $listeners;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventListenerInterface ...$listeners Event listeners.
	 */
	public function __construct( ApplicationEventListenerInterface ...$listeners ) {

		$this->listeners = $listeners;
	}

	/**
	 * Dispatches the given application event to configured listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event to dispatch.
	 *
	 * @throws ApplicationEventBusException When dispatching fails.
	 */
	#[Override]
	public function publish( ApplicationEventInterface $event ): void {

		try {

			foreach ( $this->listeners as $listener ) {
				$listener->handle( $event );
			}
		} catch ( Throwable $e ) {

			throw new ApplicationEventBusException(
				sprintf(
					'Failed to dispatch application event "%s".',
					$event::class,
				),
				previous: $e,
			);
		}
	}
}
