<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;
use Override;

/**
 * Registers all WordPress hook dispatchers.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class HookDispatcherRegistrar implements HookDispatcherRegistrarPort {

	/**
	 * The configured hook dispatchers.
	 *
	 * @var array<int, HookDispatcherInterface>
	 *
	 * @phpstan-var list<HookDispatcherInterface>
	 */
	private array $hook_dispatchers;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param HookDispatcherInterface ...$hook_dispatchers The hook dispatchers to register.
	 */
	public function __construct( HookDispatcherInterface ...$hook_dispatchers ) {

		$this->hook_dispatchers = $hook_dispatchers;
	}

	/**
	 * Registers all configured hook dispatchers.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register_all(): void {

		foreach ( $this->hook_dispatchers as $dispatcher ) {
			$dispatcher->register();
		}
	}
}
