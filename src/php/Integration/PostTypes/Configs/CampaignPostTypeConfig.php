<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes\Configs;

use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldType;
use Override;

/**
 * Provides configuration for the campaign post type.
 *
 * @since 1.0.0
 *
 * @internal
 */
class CampaignPostTypeConfig implements PostTypeConfigInterface {

	public const string ID = 'fundrik_campaign';

	public const string ENTITY_VERSION_FIELD_NAME = 'fundrik_campaign_version';

	/**
	 * Stores whether the campaign accepts donations.
	 */
	#[PostTypeMetaField( type: PostTypeMetaFieldType::Boolean, default: true )]
	public const string META_ACCEPTS_DONATIONS = 'fundrik_campaign_accepts_donations';

	/**
	 * Stores whether the campaign has a fundraising target.
	 */
	#[PostTypeMetaField( type: PostTypeMetaFieldType::Boolean, default: false )]
	public const string META_HAS_TARGET = 'fundrik_campaign_has_target';

	/**
	 * Stores the fundraising target amount in minor units.
	 */
	#[PostTypeMetaField( type: PostTypeMetaFieldType::Integer )]
	public const string META_TARGET_AMOUNT = 'fundrik_campaign_target_amount';

	/**
	 * Stores the fundraising target currency as an ISO 4217 code.
	 */
	#[PostTypeMetaField( type: PostTypeMetaFieldType::String, default: 'RUB' )]
	public const string META_TARGET_CURRENCY = 'fundrik_campaign_target_currency';

	/**
	 * Returns the post type ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string The post type ID.
	 *
	 * @phpstan-return non-empty-lowercase-string
	 */
	#[Override]
	public function get_id(): string {

		return self::ID;
	}

	/**
	 * Returns the post type slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string The post type slug.
	 */
	#[Override]
	public function get_slug(): string {

		return 'campaigns';
	}

	/**
	 * Returns the block editor template applied to this post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array<int, string>> The block template configuration.
	 *
	 * @phpstan-return list<list<string>>
	 */
	#[Override]
	public function get_block_template(): array {

		return [
			[ 'fundrik/campaign-settings' ],
			[ 'fundrik/donation-form' ],
		];
	}

	/**
	 * Returns the list of blocks that are explicitly available for this post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> The list of allowed block names.
	 *
	 * @phpstan-return list<string>
	 */
	#[Override]
	public function get_specific_blocks(): array {

		return [
			'fundrik/campaign-settings',
			'fundrik/donation-form',
		];
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns localized labels for the campaign post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> The list of localized label strings.
	 */
	#[Override]
	public function get_labels(): array {

		return [
			'name' => __( 'Campaigns', 'fundrik' ),
			'singular_name' => __( 'Campaign', 'fundrik' ),
			'menu_name' => __( 'Campaigns', 'fundrik' ),
			'name_admin_bar' => __( 'Campaign', 'fundrik' ),
			'add_new' => __( 'Add New', 'fundrik' ),
			'add_new_item' => __( 'Add New Campaign', 'fundrik' ),
			'new_item' => __( 'New Campaign', 'fundrik' ),
			'edit_item' => __( 'Edit Campaign', 'fundrik' ),
			'view_item' => __( 'View Campaign', 'fundrik' ),
			'all_items' => __( 'All Campaigns', 'fundrik' ),
			'search_items' => __( 'Search Campaigns', 'fundrik' ),
			'parent_item_colon' => __( 'Parent Campaigns:', 'fundrik' ),
			'not_found' => __( 'No campaigns found.', 'fundrik' ),
			'not_found_in_trash' => __( 'No campaigns found in Trash.', 'fundrik' ),
			'featured_image' => __( 'Campaign Cover Image', 'fundrik' ),
			'set_featured_image' => __( 'Set campaign cover image', 'fundrik' ),
			'remove_featured_image' => __( 'Remove campaign cover image', 'fundrik' ),
			'use_featured_image' => __( 'Use as campaign cover image', 'fundrik' ),
			'archives' => __( 'Campaign archives', 'fundrik' ),
			'insert_into_item' => __( 'Insert into campaign', 'fundrik' ),
			'uploaded_to_this_item' => __( 'Uploaded to this campaign', 'fundrik' ),
			'items_list' => __( 'Campaigns list', 'fundrik' ),
			'items_list_navigation' => __( 'Campaigns list navigation', 'fundrik' ),
			'filter_items_list' => __( 'Filter campaigns list', 'fundrik' ),
		];
	}
	// phpcs:enable
}
