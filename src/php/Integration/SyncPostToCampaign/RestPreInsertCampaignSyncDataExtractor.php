<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
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
	 * @param PostTypeMetaFieldReader $meta_field_reader Reads post meta defaults from attributes.
	 */
	public function __construct(
		private PostTypeMetaFieldReader $meta_field_reader,
	) {}

	// phpcs:disable Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	/**
	 * Extracts and normalizes the synchronization data from the REST request.
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass $prepared_post The prepared post object.
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return RestCampaignSyncDataDto|WP_Error The normalized data, or a WP_Error when the payload is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	public function extract_or_error(
		stdClass $prepared_post,
		WP_REST_Request $request,
	): RestCampaignSyncDataDto|WP_Error {

		/** @var array<string, mixed> $params */
		$params = $request->get_json_params();

		try {

			// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong
			$id = ArrayExtractor::extract_int_required( $params, 'id' );
			$title = $this->resolve_title_or_fallback( $params, $id );

			/** @var array<string, mixed> $meta */
			$meta = ArrayExtractor::extract_array_required( $params, 'meta' );
			$version = ArrayExtractor::extract_int_required( $meta, CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME );

			$default_accepts_donations = $this->get_meta_default_bool_or_fail( CampaignPostTypeConfig::META_ACCEPTS_DONATIONS );
			$default_has_target = $this->get_meta_default_bool_or_fail( CampaignPostTypeConfig::META_HAS_TARGET );
			$default_target_currency = $this->get_meta_default_string_or_fail( CampaignPostTypeConfig::META_TARGET_CURRENCY );
			$has_target = ArrayExtractor::extract_bool_optional( $meta, CampaignPostTypeConfig::META_HAS_TARGET ) ?? $default_has_target;

			return new RestCampaignSyncDataDto(
				id: EntityId::create( $id ),
				title: $title,
				version: EntityVersion::create( $version ),
				accepts_donations: ArrayExtractor::extract_bool_optional( $meta, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS ) ?? $default_accepts_donations,
				has_target: $has_target,
				target_amount: $has_target ? $this->extract_target_amount_or_null( $meta ) : null,
				target_currency: ArrayExtractor::extract_string_optional( $meta, CampaignPostTypeConfig::META_TARGET_CURRENCY ) ?? $default_target_currency,
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
	 * Returns a boolean default for the given campaign meta key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_key The campaign post meta key.
	 *
	 * @return bool The boolean default value.
	 */
	private function get_meta_default_bool_or_fail( string $meta_key ): bool {

		$default = $this->meta_field_reader->get_meta_default_by_config_class(
			CampaignPostTypeConfig::class,
			$meta_key,
		);

		if ( $default !== null ) {
			return TypeCaster::to_bool( $default );
		}

		throw new InvalidArgumentException(
			sprintf( 'Campaign post meta default must exist. Given: %s.', $meta_key ),
		);
	}

	/**
	 * Returns a string default for the given campaign meta key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_key The campaign post meta key.
	 *
	 * @return string The string default value.
	 */
	private function get_meta_default_string_or_fail( string $meta_key ): string {

		$default = $this->meta_field_reader->get_meta_default_by_config_class(
			CampaignPostTypeConfig::class,
			$meta_key,
		);

		if ( $default !== null ) {
			return TypeCaster::to_string( $default );
		}

		throw new InvalidArgumentException(
			sprintf( 'Campaign post meta default must exist. Given: %s.', $meta_key ),
		);
	}

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
	private function extract_target_amount_or_null( array $meta ): ?int {

		if ( ! array_key_exists( CampaignPostTypeConfig::META_TARGET_AMOUNT, $meta ) ) {
			return null;
		}

		$value = $meta[ CampaignPostTypeConfig::META_TARGET_AMOUNT ];

		if ( $value === '' ) {
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
	private function resolve_title_or_fallback( array $params, int $post_id ): string {

		$title = ArrayExtractor::extract_string_optional( $params, 'title' );

		if ( $title !== null ) {
			return $title;
		}

		$persisted_title = get_post_field( 'post_title', $post_id, 'raw' );

		return TypeCaster::to_string( $persisted_title );
	}
}
