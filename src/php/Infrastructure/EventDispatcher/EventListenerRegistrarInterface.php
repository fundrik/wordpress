<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventDispatcher;

/**
 * Provides a method for registering all event listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface EventListenerRegistrarInterface {

	/**
	 * Registers all declared event listeners.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void;
}
