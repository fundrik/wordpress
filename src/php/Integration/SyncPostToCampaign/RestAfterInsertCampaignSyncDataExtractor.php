<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\Helpers\Meta;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use InvalidArgumentException;
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
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param PostTypeMetaFieldReader $meta_field_reader Reads post meta defaults from attributes.
	 */
	public function __construct(
		private PostTypeMetaFieldReader $meta_field_reader,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
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

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var array<string, mixed> $params */
		$params = $request->get_json_params();

		$id = TypeCaster::to_int( $post->ID );
		$title = TypeCaster::to_string( $post->post_title );
		$status = TypeCaster::to_string( $post->post_status );

		$default_is_open = $this->get_meta_default_bool_or_fail( CampaignPostTypeConfig::META_IS_OPEN );
		$default_has_target = $this->get_meta_default_bool_or_fail( CampaignPostTypeConfig::META_HAS_TARGET );
		$default_target_amount = $this->get_meta_default_int_or_fail( CampaignPostTypeConfig::META_TARGET_AMOUNT );
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		$default_target_currency = $this->get_meta_default_string_or_fail( CampaignPostTypeConfig::META_TARGET_CURRENCY );

		$is_open = TypeCaster::to_string(
			Meta::get_post_meta_or_null( $id, CampaignPostTypeConfig::META_IS_OPEN ) ?? (string) $default_is_open,
		);
		$has_target = TypeCaster::to_string(
			Meta::get_post_meta_or_null( $id, CampaignPostTypeConfig::META_HAS_TARGET )
				?? (string) $default_has_target,
		);
		$target_amount = TypeCaster::to_string(
			Meta::get_post_meta_or_null( $id, CampaignPostTypeConfig::META_TARGET_AMOUNT )
				?? (string) $default_target_amount,
		);
		$target_currency = TypeCaster::to_string(
			Meta::get_post_meta_or_null( $id, CampaignPostTypeConfig::META_TARGET_CURRENCY )
				?? $default_target_currency,
		);

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		/** @var array<string, mixed> $meta */
		$meta = ArrayExtractor::extract_array_required( $params, 'meta' );
		$version = ArrayExtractor::extract_int_required( $meta, CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME );

		return new RestCampaignSyncDataDto(
			id: EntityId::create( $id ),
			title: $title,
			version: EntityVersion::create( $version ),
			is_active: $status === 'publish',
			is_open: TypeCaster::to_bool( Meta::normalize_wp_bool_value( $is_open ) ),
			has_target: TypeCaster::to_bool( Meta::normalize_wp_bool_value( $has_target ) ),
			target_amount: TypeCaster::to_int( $target_amount ),
			target_currency: $target_currency,
		);
	}

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
			sprintf( 'Cannot resolve default for campaign post meta key. Given: %s.', $meta_key ),
		);
	}

	/**
	 * Returns an integer default for the given campaign meta key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_key The campaign post meta key.
	 *
	 * @return int The integer default value.
	 */
	private function get_meta_default_int_or_fail( string $meta_key ): int {

		$default = $this->meta_field_reader->get_meta_default_by_config_class(
			CampaignPostTypeConfig::class,
			$meta_key,
		);

		if ( $default !== null ) {
			return TypeCaster::to_int( $default );
		}

		throw new InvalidArgumentException(
			sprintf( 'Cannot resolve default for campaign post meta key. Given: %s.', $meta_key ),
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
			sprintf( 'Cannot resolve default for campaign post meta key. Given: %s.', $meta_key ),
		);
	}
	// phpcs:enable
}
