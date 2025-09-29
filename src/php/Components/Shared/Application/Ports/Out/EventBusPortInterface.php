<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Shared\Application\Ports\Out;

/**
 * Provides the outbound port for publishing application events.
 *
 * Keeps the application layer decoupled from the underlying event dispatcher implementation.
 *
 * @since 1.0.0
 */
interface EventBusPortInterface {

	/**
	 * Publishes the given event to all registered listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param object $event The event object to publish.
	 */
	public function publish( object $event ): void;
}
