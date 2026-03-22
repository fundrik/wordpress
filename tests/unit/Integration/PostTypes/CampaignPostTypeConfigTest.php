<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignPostTypeConfig::class )]
#[UsesClass( PostTypeMetaField::class )]
final class CampaignPostTypeConfigTest extends WordPressTestCase {

	private CampaignPostTypeConfig $config;

	protected function setUp(): void {

		parent::setUp();

		$this->config = new CampaignPostTypeConfig();
	}

	#[Test]
	public function it_implements_post_type_config_interface(): void {

		self::assertInstanceOf( PostTypeConfigInterface::class, $this->config );
	}

	#[Test]
	public function get_id_returns_expected_value(): void {

		self::assertSame( 'fundrik_campaign', $this->config->get_id() );
		self::assertSame( CampaignPostTypeConfig::ID, $this->config->get_id() );
	}

	#[Test]
	public function get_slug_returns_expected_value(): void {

		self::assertSame( 'campaigns', $this->config->get_slug() );
	}

	#[Test]
	public function get_block_template_returns_expected_value(): void {

		self::assertSame(
			[
				[ 'fundrik/campaign-settings' ],
				[ 'fundrik/donation-form' ],
			],
			$this->config->get_block_template(),
		);
	}

	#[Test]
	public function get_specific_blocks_returns_expected_value(): void {

		self::assertSame(
			[
				'fundrik/campaign-settings',
				'fundrik/donation-form',
			],
			$this->config->get_specific_blocks(),
		);
	}

	#[Test]
	public function get_labels_returns_expected_label_subset(): void {

		self::assertSame(
			[
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
			],
			$this->config->get_labels(),
		);
	}

	#[Test]
	public function it_declares_expected_meta_fields_via_attributes_on_constants(): void {

		$this->assert_class_constant_has_attribute(
			class_name: CampaignPostTypeConfig::class,
			constant_name: 'META_ACCEPTS_DONATIONS',
			attribute_class: PostTypeMetaField::class,
			expected_values: [
				'type' => PostTypeMetaFieldType::Boolean,
				'default' => true,
			],
		);

		$this->assert_class_constant_has_attribute(
			class_name: CampaignPostTypeConfig::class,
			constant_name: 'META_HAS_TARGET',
			attribute_class: PostTypeMetaField::class,
			expected_values: [
				'type' => PostTypeMetaFieldType::Boolean,
				'default' => false,
			],
		);

		$this->assert_class_constant_has_attribute(
			class_name: CampaignPostTypeConfig::class,
			constant_name: 'META_TARGET_AMOUNT',
			attribute_class: PostTypeMetaField::class,
			expected_values: [
				'type' => PostTypeMetaFieldType::Integer,
				'default' => null,
			],
		);

		$this->assert_class_constant_has_attribute(
			class_name: CampaignPostTypeConfig::class,
			constant_name: 'META_TARGET_CURRENCY',
			attribute_class: PostTypeMetaField::class,
			expected_values: [
				'type' => PostTypeMetaFieldType::String,
				'default' => 'RUB',
			],
		);
	}
}

