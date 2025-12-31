<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Helpers;

/**
 * Enumerates filesystem paths used by the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
enum PluginPath: string {

	// The directory containing custom Gutenberg blocks.
	case Blocks = 'assets/js/blocks/';

	// The PHP manifest file describing available blocks.
	case BlocksManifest = 'assets/js/blocks/blocks-manifest.php';

	/**
	 * Resolves the absolute filesystem path to this plugin resource.
	 *
	 * @since 1.0.0
	 *
	 * @return string The full absolute path to the plugin resource.
	 */
	public function get_full_path(): string {

		return FUNDRIK_PATH . $this->value;
	}
}
