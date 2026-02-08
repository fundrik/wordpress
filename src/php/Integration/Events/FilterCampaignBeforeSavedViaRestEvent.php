<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Events;

use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use stdClass;
use WP_Error;
use WP_REST_Request;

/**
 * Allows modifying the prepared campaign post or rejecting the REST save operation before WordPress persists it.
 *
 * // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
 * Triggered by the WordPress 'rest_pre_insert_(post_type)' filter for the campaign post type via the integration bridge.
 *
 * @since 1.0.0
 */
final class FilterCampaignBeforeSavedViaRestEvent implements RejectableFilterEventInterface {

	/**
	 * Stores the optional rejection error for aborting the REST operation.
	 *
	 * @since 1.0.0
	 */
	private ?WP_Error $rejection_error = null;

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

	/**
	 * Rejects the REST insert/update by attaching the given error.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $error Describes why the operation must be rejected.
	 */
	public function reject( WP_Error $error ): void {

		$this->rejection_error = $error;
	}

	/**
	 * Returns whether the operation was rejected.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the event contains a rejection error.
	 */
	public function is_rejected(): bool {

		return $this->rejection_error !== null;
	}

	/**
	 * Returns the rejection error, if any.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|null The rejection error or null when not rejected.
	 */
	public function get_rejection_error(): ?WP_Error {

		return $this->rejection_error;
	}
}
