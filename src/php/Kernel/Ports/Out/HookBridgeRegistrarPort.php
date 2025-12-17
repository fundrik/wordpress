<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Ports\Out;

/**
 * Provides a method for registering all hook-to-event bridges.
 *
 * @since 1.0.0
 */
interface HookBridgeRegistrarPort {

	/**
	 * Registers all declared hook-to-event bridges.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void;
}
