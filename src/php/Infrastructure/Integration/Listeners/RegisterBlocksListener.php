<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Listeners;

use Fundrik\WordPress\Application;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;

/**
 * Registers all custom blocks.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterBlocksListener {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Application $application Provides access to plugin-level paths and configuration.
	 */
	public function __construct(
		private Application $application,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.CodeAnalysis.UnusedFunctionParameter.Found
	/**
	 * Handles the given event.
	 *
	 * @since 1.0.0
	 *
	 * @param RegisterBlocksEvent $event Carries the WordPress context for block registration.
	 */
	public function handle( RegisterBlocksEvent $event ): void {

		wp_register_block_types_from_metadata_collection(
			$this->application->get_blocks_path(),
			$this->application->get_blocks_manifest_path(),
		);
	}
	// phpcs:enable
}
