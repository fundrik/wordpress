<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use WP_Post;

/**
 * Signals that a post, along with its terms and metadata, has been saved.
 *
 * Triggered by the WordPress 'wp_after_insert_post' action via the integration bridge.
 *
 * @since 1.0.0
 */
final readonly class PostSavedEvent implements EventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool $update Whether this is an existing post being updated.
	 * @param WP_Post|null $post_before Null for new posts, the WP_Post object prior
	 *                                  to the update for updated posts.
	 * @param WordPressContextInterface $context The WordPress-specific plugin context.
	 */
	public function __construct(
		public int $post_id,
		public WP_Post $post,
		public bool $update,
		public WP_Post|null $post_before,
		public WordPressContextInterface $context,
	) {}
}
