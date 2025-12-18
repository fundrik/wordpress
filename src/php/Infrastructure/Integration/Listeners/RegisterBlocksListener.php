<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Listeners;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerInterface;
use Fundrik\WordPress\Infrastructure\Integration\Blocks\BlocksPathsProviderInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;

/**
 * Registers all custom blocks.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterBlocksListener implements EventListenerInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param BlocksPathsProviderInterface $blocks_paths_provider Provides filesystem paths for the blocks registration.
	 */
	public function __construct(
		private BlocksPathsProviderInterface $blocks_paths_provider,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.Found
	/**
	 * Handles the given event.
	 *
	 * @since 1.0.0
	 *
	 * @param RegisterBlocksEvent $event Carries the WordPress context for the blocks registration.
	 */
	public function handle( RegisterBlocksEvent $event ): void {

		wp_register_block_types_from_metadata_collection(
			$this->blocks_paths_provider->get_blocks_path(),
			$this->blocks_paths_provider->get_blocks_manifest_path(),
		);
	}
	// phpcs:enable
}
