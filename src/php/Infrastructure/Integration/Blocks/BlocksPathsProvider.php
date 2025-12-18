<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Blocks;

use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;

/**
 * Resolves block-related filesystem paths based on the plugin structure.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class BlocksPathsProvider implements BlocksPathsProviderInterface {

	/**
	 * Returns the absolute path to the directory containing block sources.
	 *
	 * @since 1.0.0
	 *
	 * @return string The absolute filesystem path to the blocks directory.
	 */
	public function get_blocks_path(): string {

		return PluginPath::Blocks->get_full_path();
	}

	/**
	 * Returns the absolute path to the blocks manifest file.
	 *
	 * @since 1.0.0
	 *
	 * @return string The absolute filesystem path to the blocks manifest file.
	 */
	public function get_blocks_manifest_path(): string {

		return PluginPath::BlocksManifest->get_full_path();
	}
}
