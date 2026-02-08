<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use WP_Post;

/**
 * Signals that WordPress deleted a post.
 *
 * Triggered by the WordPress 'delete_post' action via the integration bridge.
 *
 * @since 1.0.0
 */
final readonly class ActionPostDeletedEvent implements InfrastructureEventInterface {

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
