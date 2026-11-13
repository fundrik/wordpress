<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldDefinition;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignPostTypeConfig::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldDefinition::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
final class CampaignPostTypeConfigTest extends WordPressTestCase {

	private CampaignPostTypeConfig $config;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->config = new CampaignPostTypeConfig();
	}

	#[Test]
	public function entity_version_field_name_has_expected_value(): void {

		self::assertSame( 'fundrik_campaign_version', CampaignPostTypeConfig::ENTITY_VERSION_FIELD_NAME );
	}

	#[Test]
	public function meta_fields_have_expected_definitions(): void {

		self::assertEquals(
			[
				'fundrik_campaign_accepts_donations' => new PostTypeMetaFieldDefinition(
					'fundrik_campaign_accepts_donations',
					WpSchemaType::Boolean,
				),
				'fundrik_campaign_has_target' => new PostTypeMetaFieldDefinition(
					'fundrik_campaign_has_target',
					WpSchemaType::Boolean,
				),
				'fundrik_campaign_target_amount' => new PostTypeMetaFieldDefinition(
					'fundrik_campaign_target_amount',
					WpSchemaType::Integer,
				),
				'fundrik_campaign_target_currency' => new PostTypeMetaFieldDefinition(
					'fundrik_campaign_target_currency',
					WpSchemaType::String,
				),
			],
			( new PostTypeMetaFieldReader() )->get_meta_fields( $this->config ),
		);
	}

	#[Test]
	public function get_id_returns_campaign_post_type_id(): void {

		self::assertSame( 'fundrik_campaign', $this->config->get_id() );
	}

	#[Test]
	public function get_slug_returns_campaigns_slug(): void {

		self::assertSame( 'campaigns', $this->config->get_slug() );
	}

	#[Test]
	public function get_block_template_returns_campaign_blocks_in_editor_order(): void {

		self::assertSame(
			[
				[ 'fundrik/campaign-settings' ],
				[ 'fundrik/campaign-summary' ],
				[ 'fundrik/donation-form' ],
			],
			$this->config->get_block_template(),
		);
	}

	#[Test]
	public function get_specific_blocks_returns_campaign_block_names(): void {

		self::assertSame(
			[
				'fundrik/campaign-settings',
				'fundrik/campaign-summary',
				'fundrik/donation-form',
			],
			$this->config->get_specific_blocks(),
		);
	}

	#[Test]
	public function get_labels_returns_campaign_labels(): void {

		self::assertSame(
			[
				'name' => 'Campaigns',
				'singular_name' => 'Campaign',
				'menu_name' => 'Campaigns',
				'name_admin_bar' => 'Campaign',
				'add_new' => 'Add New',
				'add_new_item' => 'Add New Campaign',
				'new_item' => 'New Campaign',
				'edit_item' => 'Edit Campaign',
				'view_item' => 'View Campaign',
				'all_items' => 'Campaigns',
				'search_items' => 'Search Campaigns',
				'parent_item_colon' => 'Parent Campaigns:',
				'not_found' => 'No campaigns found.',
				'not_found_in_trash' => 'No campaigns found in Trash.',
				'featured_image' => 'Campaign Cover Image',
				'set_featured_image' => 'Set campaign cover image',
				'remove_featured_image' => 'Remove campaign cover image',
				'use_featured_image' => 'Use as campaign cover image',
				'archives' => 'Campaign archives',
				'insert_into_item' => 'Insert into campaign',
				'uploaded_to_this_item' => 'Uploaded to this campaign',
				'items_list' => 'Campaigns list',
				'items_list_navigation' => 'Campaigns list navigation',
				'filter_items_list' => 'Filter campaigns list',
			],
			$this->config->get_labels(),
		);
	}
}
