<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\WordPressContext;

/**
 * Provides methods for accessing WordPress-specific plugin context.
 *
 * @since 1.0.0
 */
interface WordPressContextInterface {

	/**
	 * Returns the list of declared post type class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> The list of post type class names.
	 *
	 * @phpstan-return list<class-string<\Fundrik\WordPress\Infrastructure\Integration\PostTypes\PostTypeInterface>>
	 */
	public function get_declared_post_type_classes(): array;

	/**
	 * Retrieves the registered WordPress post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, \WP_Post_Type> Registered post type objects keyed by slug.
	 */
	public function get_registered_post_types(): array;

	/**
	 * Retrieves the registered WordPress block types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, \WP_Block_Type> Registered block type objects keyed by name.
	 */
	public function get_registered_block_types(): array;
}
