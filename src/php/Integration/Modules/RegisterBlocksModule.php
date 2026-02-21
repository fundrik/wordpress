<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Modules;

use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;

/**
 * Registers all custom blocks declared in the plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterBlocksModule implements ModuleInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param InitActionHookDispatcher $init_hook Dispatches the WordPress 'init' action to attached listeners.
	 */
	public function __construct(
		private InitActionHookDispatcher $init_hook,
	) {}

	/**
	 * Attaches the block registration callback to the WordPress 'init' action.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {

		$this->init_hook->attach( $this->register_blocks( ... ) );
	}

	/**
	 * Registers the block types from the metadata collection.
	 *
	 * @since 1.0.0
	 */
	private function register_blocks(): void {

		wp_register_block_types_from_metadata_collection(
			PluginPath::Blocks->get_full_path(),
			PluginPath::BlocksManifest->get_full_path(),
		);
	}
}
