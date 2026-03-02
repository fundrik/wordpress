<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports;

/**
 * Provides the outbound port for registering hook dispatchers.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface HookDispatcherRegistrarPort {

	/**
	 * Registers all declared hook dispatchers.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void;
}
