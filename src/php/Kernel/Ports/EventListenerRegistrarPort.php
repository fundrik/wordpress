<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports;

/**
 * Provides methods for registering all infrastructure event listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface EventListenerRegistrarPort {

	/**
	 * Registers all declared infrastructure event listeners.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void;
}
