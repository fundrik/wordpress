<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Helpers;

/**
 * Enumerates URL paths used by the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
enum PluginUrl: string {

	// The base URL for all plugin assets.
	case Assets = 'assets/';

	// The URL for Gutenberg block assets.
	case Blocks = 'assets/js/blocks/';

	// The URL for editor-specific scripts.
	case EditorScripts = 'assets/js/';

	/**
	 * Resolves the absolute URL to this plugin resource.
	 *
	 * @since 1.0.0
	 *
	 * @return string The full absolute URL to the plugin resource.
	 */
	public function get_full_url(): string {

		return FUNDRIK_URL . $this->value;
	}
}
