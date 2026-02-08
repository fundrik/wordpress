<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

/**
 * Provides methods for describing configuration of a custom post type.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface PostTypeInterface {

	/**
	 * Returns localized labels for the post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> The associative array of label strings.
	 */
	public function get_labels(): array;
}
