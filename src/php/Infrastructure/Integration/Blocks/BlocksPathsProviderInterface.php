<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Blocks;

/**
 * Provides methods for resolving filesystem paths required for block registration.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface BlocksPathsProviderInterface {

	/**
	 * Returns the absolute path to the directory containing block sources.
	 *
	 * @since 1.0.0
	 *
	 * @return string The absolute filesystem path to the blocks directory.
	 */
	public function get_blocks_path(): string;

	/**
	 * Returns the absolute path to the blocks manifest file.
	 *
	 * @since 1.0.0
	 *
	 * @return string The absolute filesystem path to the blocks manifest file.
	 */
	public function get_blocks_manifest_path(): string;
}
