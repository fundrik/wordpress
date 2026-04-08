<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use InvalidArgumentException;
use stdClass;
use WP_Error;
use WP_REST_Request;

/**
 * Extracts the campaign synchronization data from the REST pre-insert stage.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestPreInsertCampaignSyncDataExtractor {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsReader $settings_reader Reads campaign defaults from admin settings.
	 */
	public function __construct(
		private AdminSettingsReader $settings_reader,
	) {}

	// phpcs:disable Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint, SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Extracts and normalizes the synchronization data from the REST request.
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass $prepared_post The prepared post object.
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return RestCampaignSyncData|WP_Error The normalized data, or a WP_Error when the payload is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	public function extract_or_error( stdClass $prepared_post, WP_REST_Request $request ): RestCampaignSyncData|WP_Error {

		/** @var array<string, mixed> $params */
		$params = $request->get_json_params();

		try {

			// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong
			$id = ArrayExtractor::extract_int_required( $params, 'id' );
			$title = $this->resolve_title( $params, $id );

			/** @var array<string, mixed> $meta */
			$meta = ArrayExtractor::extract_array_required( $params, 'meta' );
			// The client must send the current entity version for optimistic locking.
			$version = ArrayExtractor::extract_int_required( $meta, CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME );

			$default_accepts_donations = $this->settings_reader->get_campaign_default_accepts_donations();
			$default_has_target = $this->settings_reader->get_campaign_default_has_target();
			$default_target_currency = $this->settings_reader->get_currency();

			// The REST payload may omit unchanged optional meta fields.
			$accepts_donations = ArrayExtractor::extract_bool_optional( $meta, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS ) ?? $default_accepts_donations;
			$has_target = ArrayExtractor::extract_bool_optional( $meta, CampaignPostTypeConfig::META_HAS_TARGET ) ?? $default_has_target;
			$target_amount = $has_target ? $this->extract_target_amount( $meta ) : null;
			$target_currency = ArrayExtractor::extract_string_optional( $meta, CampaignPostTypeConfig::META_TARGET_CURRENCY ) ?? $default_target_currency;

			return new RestCampaignSyncData(
				id: EntityId::create( $id ),
				title: $title,
				version: EntityVersion::create( $version ),
				accepts_donations: $accepts_donations,
				has_target: $has_target,
				target_amount: $target_amount,
				target_currency: $target_currency,
			);
			// phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong

		} catch ( ArrayExtractionException | InvalidArgumentException $e ) {

			return new WP_Error(
				'fundrik_campaign_invalid_payload',
				$e->getMessage(),
				[ 'status' => 422 ],
			);
		}
	}
	// phpcs:enable

	/**
	 * Returns the target amount from meta, treating an empty string as null.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $meta The REST meta payload.
	 *
	 * @return int|null The extracted target amount, or null when empty.
	 *
	 * @throws ArrayExtractionException When the amount is present but invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function extract_target_amount( array $meta ): ?int {

		if ( ! array_key_exists( CampaignPostTypeConfig::META_TARGET_AMOUNT, $meta ) ) {
			return null;
		}

		$value = $meta[ CampaignPostTypeConfig::META_TARGET_AMOUNT ];

		if ( $value === '' ) {
			// Treat a cleared form field as an omitted target amount in the REST payload.
			return null;
		}

		return ArrayExtractor::extract_int_nullable_optional( $meta, CampaignPostTypeConfig::META_TARGET_AMOUNT );
	}

	/**
	 * Returns the campaign title from the payload, prepared post, or persisted post.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $params The REST request payload.
	 * @param int $post_id The campaign post ID.
	 *
	 * @return string The campaign title.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function resolve_title( array $params, int $post_id ): string {

		$title = ArrayExtractor::extract_string_optional( $params, 'title' );

		if ( $title !== null ) {
			return $title;
		}

		// Reuse the persisted title when the REST payload omits it during partial updates.
		$persisted_title = get_post_field( 'post_title', $post_id, 'raw' );

		return TypeCaster::to_string( $persisted_title );
	}
}
