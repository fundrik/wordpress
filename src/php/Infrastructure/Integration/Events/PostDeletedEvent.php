<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use WP_Post;

/**
 * Signals that a WordPress post has been deleted.
 *
 * Triggered by the WordPress 'delete_post' action via the integration bridge.
 *
 * @since 1.0.0
 */
final readonly class PostDeletedEvent implements InfrastructureEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param WordPressContextInterface $context The WordPress-specific plugin context.
	 */
	public function __construct(
		public int $post_id,
		public WP_Post $post,
		public WordPressContextInterface $context,
	) {}
}
