<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Helpers;

use WP_Screen;

/**
 * Provides current admin screen inspection helpers.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CurrentAdminScreen {

	/**
	 * Checks whether the current admin screen matches the given post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Post type ID.
	 *
	 * @return bool True when the current screen matches the post type.
	 */
	public static function is_post_type( string $post_type ): bool {

		$screen = get_current_screen();

		return $screen instanceof WP_Screen
			&& $screen->post_type === $post_type;
	}
}
