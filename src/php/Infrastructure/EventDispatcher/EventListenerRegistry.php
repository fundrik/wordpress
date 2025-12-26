<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\FilterAllowedBlocksByPostTypeListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterBlocksListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterPostTypesListener;

/**
 * Provides the map of infrastructure events to their listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
class EventListenerRegistry {

	/**
	 * Returns the map of infrastructure event class names to listener class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> The event-to-listener map.
	 *
	 * @phpstan-return array<
	 *     class-string<InfrastructureEventInterface>,
	 *     class-string<InfrastructureEventListenerInterface>
	 * >
	 */
	public function get_event_listener_map(): array {

		return [
			RegisterPostTypesEvent::class => RegisterPostTypesListener::class,
			RegisterBlocksEvent::class => RegisterBlocksListener::class,
			FilterAllowedBlockTypesEvent::class => FilterAllowedBlocksByPostTypeListener::class,
		];
	}
}
