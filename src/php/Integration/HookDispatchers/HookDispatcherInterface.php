<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

/**
 * Provides methods for attaching listeners and registering WordPress hooks.
 *
 * Hook dispatchers are responsible for validating WordPress input and for
 * shielding hook execution from listener exceptions. Listener failure logging
 * is intentionally left to listeners (or boot units) to avoid duplicate logs
 * for the same exception.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface HookDispatcherInterface {

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
