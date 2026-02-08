<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use WP_Post;
use WP_REST_Request;

/**
 * Signals that WordPress saved a campaign via REST.
 *
 * Triggered by the WordPress 'rest_after_insert_{post_type}' action.
 *
 * @since 1.0.0
 */
final readonly class ActionCampaignSavedViaRestEvent implements InfrastructureEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Inserted or updated post object.
	 * @param WP_REST_Request $request Request object.
	 * @param bool $creating True when creating a post, false when updating.
	 * @param WordPressContextInterface $context The WordPress-specific plugin context.
	 */
	public function __construct(
		public WP_Post $post,
		public WP_REST_Request $request,
		public bool $creating,
		public WordPressContextInterface $context,
	) {}
}
