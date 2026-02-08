<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeBlockTemplate;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeBlockTemplateReader;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSlug;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSlugReader;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlock;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlockReader;
use Fundrik\WordPress\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Integration\PostTypes\PostTypeInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignPostType::class )]
#[UsesClass( PostTypeBlockTemplate::class )]
#[UsesClass( PostTypeBlockTemplateReader::class )]
#[UsesClass( PostTypeId::class )]
#[UsesClass( PostTypeIdReader::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
#[UsesClass( PostTypeSlug::class )]
#[UsesClass( PostTypeSlugReader::class )]
#[UsesClass( PostTypeSpecificBlock::class )]
#[UsesClass( PostTypeSpecificBlockReader::class )]
final class CampaignPostTypeTest extends WordPressTestCase {

	private CampaignPostType $post_type;

	protected function setUp(): void {

		parent::setUp();

		$this->post_type = new CampaignPostType();
	}

	#[Test]
	public function it_implements_post_type_interface(): void {

		self::assertInstanceOf( PostTypeInterface::class, $this->post_type );
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
			$this->post_type->get_labels(),
		);
	}

	#[Test]
	public function it_is_readable_by_all_post_type_readers(): void {

		$id_reader = new PostTypeIdReader();
		$slug_reader = new PostTypeSlugReader();
		$template_reader = new PostTypeBlockTemplateReader();
		$block_reader = new PostTypeSpecificBlockReader();
		$meta_reader = new PostTypeMetaFieldReader();

		self::assertSame( 'fundrik_campaign', $id_reader->get_id( CampaignPostType::class ) );
		self::assertSame( 'campaigns', $slug_reader->get_slug( CampaignPostType::class ) );
		self::assertSame(
			[ [ 'fundrik/campaign-settings' ] ],
			$template_reader->get_template( CampaignPostType::class ),
		);
		self::assertSame( [ 'fundrik/campaign-settings' ], $block_reader->get_blocks( CampaignPostType::class ) );

		self::assertSame(
			[
				CampaignPostType::META_IS_OPEN => [
					'type' => 'boolean',
					'default' => true,
				],
				CampaignPostType::META_HAS_TARGET => [ 'type' => 'boolean' ],
				CampaignPostType::META_TARGET_AMOUNT => [ 'type' => 'number' ],
			],
			$meta_reader->get_meta_fields( CampaignPostType::class ),
		);
	}
}
