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

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, Generic.Commenting.DocComment.MissingShort, SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	/**
	 * Extracts and normalizes the synchronization data from the saved post snapshot.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The saved post.
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return RestCampaignSyncData The normalized data.
	 *
	 * @throws InvalidArgumentException When the payload is not usable.
	 */
	public function extract( WP_Post $post, WP_REST_Request $request ): RestCampaignSyncData {

		/** @var array<string, mixed> $params */
		$params = $request->get_json_params();

		$id = TypeCaster::to_int( $post->ID );
		$title = TypeCaster::to_string( $post->post_title );

		// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator.MultiLineTernaryOperatorNotUsed
		$default_accepts_donations = $this->get_meta_default_bool_or_fail( CampaignPostTypeConfig::META_ACCEPTS_DONATIONS );
		$default_has_target = $this->get_meta_default_bool_or_fail( CampaignPostTypeConfig::META_HAS_TARGET );
		$default_target_currency = $this->get_meta_default_string_or_fail( CampaignPostTypeConfig::META_TARGET_CURRENCY );

		$accepts_donations = Meta::get_post_meta_bool_or_null( $id, CampaignPostTypeConfig::META_ACCEPTS_DONATIONS ) ?? $default_accepts_donations;
		$has_target = Meta::get_post_meta_bool_or_null( $id, CampaignPostTypeConfig::META_HAS_TARGET ) ?? $default_has_target;
		$target_amount = $has_target ? Meta::get_post_meta_int_or_null( $id, CampaignPostTypeConfig::META_TARGET_AMOUNT ) : null;
		$target_currency = Meta::get_post_meta_string_or_null( $id, CampaignPostTypeConfig::META_TARGET_CURRENCY ) ?? $default_target_currency;
		// phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.ControlStructures.RequireMultiLineTernaryOperator.MultiLineTernaryOperatorNotUsed

		/** @var array<string, mixed> $meta */
		$meta = ArrayExtractor::extract_array_required( $params, 'meta' );
		$version = ArrayExtractor::extract_int_required( $meta, CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME );

		return new RestCampaignSyncData(
			id: EntityId::create( $id ),
			title: $title,
			version: EntityVersion::create( $version ),
			accepts_donations: $accepts_donations,
			has_target: $has_target,
			target_amount: $target_amount,
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
	// phpcs:enable
}
