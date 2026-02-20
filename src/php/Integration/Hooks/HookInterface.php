<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Hooks;

/**
 * Provides methods for attaching listeners and registering WordPress hooks.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface HookInterface {

	/**
	 * Attaches the given listener to the hook.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $listener Handles the hook dispatch.
	 */
	public function attach( callable $listener ): void;

	/**
	 * Registers the WordPress hook callback.
	 *
	 * @since 1.0.0
	 */
	public function register(): void;
}
