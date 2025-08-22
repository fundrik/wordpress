<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges;

/**
 * Provides methods for bridging a specific WordPress hook to an internal events.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface HookToEventBridgeInterface {

	/**
	 * Registers a WordPress hook and bridge it to an internal events.
	 *
	 * Skips events dispatching if input is invalid.
	 *
	 * @since 1.0.0
	 */
	public function register(): void;
}
