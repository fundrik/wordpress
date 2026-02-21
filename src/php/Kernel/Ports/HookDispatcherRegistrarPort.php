<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports;

/**
 * Provides methods for registering all hook dispatchers.
 *
 * @since 1.0.0
 */
interface HookDispatcherRegistrarPort {

	/**
	 * Registers all declared hook dispatchers.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void;
}
