<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\Helpers\Meta;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use WP_Post;
use WP_REST_Request;

/**
 * Extracts the campaign synchronization data from the REST after-insert stage.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestAfterInsertCampaignSyncDataExtractor {

	/**
	 * Extracts and normalizes the synchronization data from the saved post snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The saved post.
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return RestCampaignSyncDataDto The normalized data.
	 *
	 * @throws InvalidArgumentException When the payload is not usable.
	 */
	public function extract( WP_Post $post, WP_REST_Request $request ): RestCampaignSyncDataDto {

		$params = $request->get_json_params();

		$id = TypeCaster::to_int( $post->ID );
		$title = TypeCaster::to_string( $post->post_title );

		$is_open = Meta::get_post_meta_or_null( $id, CampaignPostTypeConfig::META_IS_OPEN ) ?? '1';
		$has_target = Meta::get_post_meta_or_null( $id, CampaignPostTypeConfig::META_HAS_TARGET ) ?? '0';
		$target_amount = Meta::get_post_meta_or_null( $id, CampaignPostTypeConfig::META_TARGET_AMOUNT ) ?? '0';

		$meta = ArrayExtractor::extract_array_required( $params, 'meta' );
		$version = ArrayExtractor::extract_int_required( $meta, CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME );

		return new RestCampaignSyncDataDto(
			id: EntityId::create( $id ),
			title: $title,
			version: EntityVersion::create( $version ),
			is_open: TypeCaster::to_bool( Meta::normalize_wp_bool_value( $is_open ) ),
			has_target: TypeCaster::to_bool( Meta::normalize_wp_bool_value( $has_target ) ),
			target_amount: TypeCaster::to_int( $target_amount ),
		);
	}
}
