<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports\Out;

/**
 * Provides a method for registering all event listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface EventListenerRegistrarPort {

	/**
	 * Registers all declared event listeners.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void;
}
