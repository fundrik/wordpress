<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress post for tests.
 */
final class WpPost {

	/**
	 * Post ID.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress WP_Post API.
	public int|string $ID;

	/**
	 * Post title.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress WP_Post API.
	public string $post_title;

	/**
	 * Post type slug.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress WP_Post API.
	public string $post_type;

	/**
	 * Post status.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress WP_Post API.
	public string $post_status;

	/**
	 * Creates the post.
	 *
	 * @param string $post_type Post type slug.
	 * @param int|string $id Post ID.
	 * @param string $title Post title.
	 * @param string $status Post status.
	 */
	public function __construct(
		string $post_type = 'post',
		int|string $id = 1,
		string $title = 'Post title',
		string $status = 'publish',
	) {

		$this->post_type = $post_type;
		$this->ID = $id;
		$this->post_title = $title;
		$this->post_status = $status;
	}
}
