<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Listeners;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventListenerInterface;
use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;
use Fundrik\WordPress\Integration\Events\ActionRegisterBlocksEvent;

/**
 * Registers all custom blocks.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterBlocksListener implements InfrastructureEventListenerInterface {

	// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.Found
	/**
	 * Handles the given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ActionRegisterBlocksEvent $event Carries the WordPress context for the blocks registration.
	 */
	public function handle( ActionRegisterBlocksEvent $event ): void {

		wp_register_block_types_from_metadata_collection(
			PluginPath::Blocks->get_full_path(),
			PluginPath::BlocksManifest->get_full_path(),
		);
	}
	// phpcs:enable
}
