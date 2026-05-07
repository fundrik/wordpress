<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventBus;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Override;
use Throwable;

/**
 * Dispatches application events to configured consumers.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ApplicationEventBus implements ApplicationEventBusPort {

	/**
	 * Configured event consumers.
	 *
	 * @var list<ApplicationEventConsumerInterface>
	 */
	private array $consumers;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventConsumerInterface ...$consumers Event consumers.
	 */
	public function __construct( ApplicationEventConsumerInterface ...$consumers ) {

		$this->consumers = $consumers;
	}

	/**
	 * Dispatches the given application event to configured consumers.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event to dispatch.
	 */
	#[Override]
	public function publish( ApplicationEventInterface $event ): void {

		foreach ( $this->consumers as $consumer ) {

			try {
				$consumer->consume( $event );
			} catch ( Throwable ) {
				// Consumers are responsible for handling and logging their own failures.
				continue;
			}
		}
	}
}
