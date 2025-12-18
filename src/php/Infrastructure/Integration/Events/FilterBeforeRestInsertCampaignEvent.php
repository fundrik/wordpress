<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use stdClass;
use WP_REST_Request;

/**
 * Signals that a campaign post prepared via the REST API should be filtered before insertion.
 *
 * // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
 * Triggered by the WordPress 'rest_pre_insert_(post_type)' filter for the campaign post type via the integration bridge.
 *
 * @since 1.0.0
 */
final class FilterBeforeRestInsertCampaignEvent implements EventInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.Commenting.DocCommentSpacing.IncorrectLinesCountBetweenDifferentAnnotationsTypes
	 * @param stdClass $prepared_post An object representing a single post prepared for inserting or updating the database.
	 * @param WP_REST_Request $request Request object.
	 * @param WordPressContextInterface $context The WordPress-specific plugin context.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function __construct(
		public stdClass $prepared_post,
		public readonly WP_REST_Request $request,
		public readonly WordPressContextInterface $context,
	) {}
}
