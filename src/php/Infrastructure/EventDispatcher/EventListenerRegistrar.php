<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\FilterAllowedBlocksByPostTypeListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterBlocksListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterPostTypesListener;
use Fundrik\WordPress\Kernel\Ports\Out\EventListenerRegistrarPort;

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
	 * @param EventDispatcherInterface $dispatcher Registers event listeners.
	 */
	public function __construct(
		private EventDispatcherInterface $dispatcher,
	) {}

	/**
	 * Registers all declared event listeners.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void {

		$this->dispatcher->listen( RegisterPostTypesEvent::class, RegisterPostTypesListener::class );
		$this->dispatcher->listen( RegisterBlocksEvent::class, RegisterBlocksListener::class );

		$this->dispatcher->listen( FilterAllowedBlockTypesEvent::class, FilterAllowedBlocksByPostTypeListener::class );
	}
}
