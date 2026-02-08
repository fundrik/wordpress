<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\WordPressContext;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use WP_Block_Type;
use WP_Block_Type_Registry;
use WP_Post_Type;

#[CoversClass( WordPressContext::class )]
#[UsesClass( CampaignPostType::class )]
final class WordPressContextTest extends MockeryTestCase {

	private WordPressContext $context;

	protected function setUp(): void {

		parent::setUp();

		$this->context = new WordPressContext();
	}

	#[Test]
	public function get_declared_post_type_classes_returns_expected_list(): void {

		self::assertSame(
			[
				CampaignPostType::class,
			],
			$this->context->get_declared_post_type_classes(),
		);
	}

	#[Test]
	public function get_registered_post_types_caches_wordpress_result(): void {

		$post_types = [
			'fundrik_campaign' => Mockery::mock( WP_Post_Type::class ),
		];

		Functions\expect( 'get_post_types' )
			->once()
			->with( [], 'objects' )
			->andReturn( $post_types );

		$first = $this->context->get_registered_post_types();
		$second = $this->context->get_registered_post_types();

		self::assertSame( $post_types, $first );
		self::assertSame( $post_types, $second );
	}

	#[Test]
	public function get_registered_block_types_caches_registry_result(): void {

		$blocks = [
			'fundrik/campaign-settings' => Mockery::mock( WP_Block_Type::class ),
		];

		$instance = Mockery::mock( WP_Block_Type_Registry::class );

		$instance
			->shouldReceive( 'get_all_registered' )
			->once()
			->andReturn( $blocks );

		WP_Block_Type_Registry::set_instance( $instance );

		$first = $this->context->get_registered_block_types();
		$second = $this->context->get_registered_block_types();

		self::assertSame( $blocks, $first );
		self::assertSame( $blocks, $second );
	}
}
