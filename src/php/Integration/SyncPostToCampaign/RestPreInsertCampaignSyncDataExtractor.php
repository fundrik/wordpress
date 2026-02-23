<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
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

	// // phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint, SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	/**
	 * Extracts and normalizes the synchronization data from the REST request.
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass $prepared_post The prepared post object.
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return RestCampaignSyncDataDto|WP_Error The normalized data, or a WP_Error when the payload is invalid.
	 */
	public function extract_or_error(
		stdClass $prepared_post,
		WP_REST_Request $request,
	): RestCampaignSyncDataDto|WP_Error {

		/** @var array<string, mixed> $params */
		$params = $request->get_json_params();

		try {

			// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator.MultiLineTernaryOperatorNotUsed
			$id = ArrayExtractor::extract_int_required( $params, 'id' );
			$title = ArrayExtractor::extract_string_optional( $params, 'title' ) ?? 'Unchanged title';

			/** @var array<string, mixed> $meta */
			$meta = ArrayExtractor::extract_array_required( $params, 'meta' );
			$version = ArrayExtractor::extract_int_required( $meta, CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME );

			return new RestCampaignSyncDataDto(
				id: EntityId::create( $id ),
				title: $title,
				version: EntityVersion::create( $version ),
				is_open: ArrayExtractor::extract_bool_optional( $meta, CampaignPostTypeConfig::META_IS_OPEN ) ?? true,
				has_target: ArrayExtractor::extract_bool_optional( $meta, CampaignPostTypeConfig::META_HAS_TARGET ) ?? false,
				target_amount: ArrayExtractor::extract_int_optional( $meta, CampaignPostTypeConfig::META_TARGET_AMOUNT ) ?? 0,
			);
			// phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator.MultiLineTernaryOperatorNotUsed

		} catch ( ArrayExtractionException | InvalidArgumentException $e ) {

			return new WP_Error(
				'fundrik_campaign_invalid_payload',
				$e->getMessage(),
				[ 'status' => 422 ],
			);
		}
	}
	// phpcs:enable
}
