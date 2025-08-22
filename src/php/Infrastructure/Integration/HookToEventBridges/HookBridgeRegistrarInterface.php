<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges;

/**
 * Provides a method for registering all hook-to-event bridges.
 *
 * @since 1.0.0
 */
interface HookBridgeRegistrarInterface {

	/**
	 * Registers all declared hook-to-event bridges.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void;
}
