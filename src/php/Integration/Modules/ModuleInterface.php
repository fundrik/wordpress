<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Modules;

/**
 * Provides methods for bootstrapping integration modules.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface ModuleInterface {

	/**
	 * Bootstraps the module by attaching its integrations.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void;
}
