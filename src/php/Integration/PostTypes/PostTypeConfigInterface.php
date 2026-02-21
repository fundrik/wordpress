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
interface PostTypeConfigInterface {

	/**
	 * Returns the post type ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string The post type ID.
	 */
	public function get_id(): string;

	/**
	 * Returns the post type slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string The post type slug.
	 */
	public function get_slug(): string;

	/**
	 * Returns the block editor template applied to this post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<int, string>> The block template configuration.
	 *
	 * @phpstan-return list<list<string>>
	 */
	public function get_block_template(): array;

	/**
	 * Returns the list of blocks that are explicitly available for this post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> The list of allowed block names.
	 *
	 * @phpstan-return list<string>
	 */
	public function get_specific_blocks(): array;

	/**
	 * Returns localized labels for the post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> The associative array of label strings.
	 */
	public function get_labels(): array;
}
