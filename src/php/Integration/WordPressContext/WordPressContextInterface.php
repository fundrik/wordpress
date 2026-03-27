<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\WordPressContext;

use WP_Block_Type;
use WP_Post_Type;

/**
 * Provides methods for accessing WordPress-specific plugin context.
 *
 * @since 1.0.0
 */
interface WordPressContextInterface {

	/**
	 * Retrieves the registered WordPress post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, WP_Post_Type> Registered post type objects keyed by slug.
	 */
	public function get_registered_post_types(): array;

	/**
	 * Retrieves the registered WordPress block types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, WP_Block_Type> Registered block type objects keyed by name.
	 */
	public function get_registered_block_types(): array;
}
