<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Allows modifying the REST response returned for a campaign before it is sent to the client.
 *
 * Triggered by the WordPress 'rest_prepare_(post_type)' filter via the integration bridge.
 *
 * @since 1.0.0
 */
final class FilterCampaignRestResponseEvent implements InfrastructureEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @param WordPressContextInterface $context The WordPress-specific plugin context.
	 */
	public function __construct(
		public WP_REST_Response $response,
		public readonly WP_Post $post,
		public readonly WP_REST_Request $request,
		public readonly WordPressContextInterface $context,
	) {}
}
