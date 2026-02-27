<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\WordPressContext;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContext;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WP_Block_Type;
use WP_Block_Type_Registry;
use WP_Post_Type;

#[CoversClass( WordPressContext::class )]
final class WordPressContextTest extends MockeryTestCase {

	private WordPressContext $context;

	protected function setUp(): void {

		parent::setUp();

		$this->context = new WordPressContext();
	}

	// ---------------------------------------------------------------------
	// get_registered_post_types()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_registered_post_types_returns_wordpress_result(): void {

		$post_types = [
			'fundrik_campaign' => Mockery::mock( WP_Post_Type::class ),
		];

		Functions\expect( 'get_post_types' )
			->once()
			->with( [], 'objects' )
			->andReturn( $post_types );

		$result = $this->context->get_registered_post_types();

		self::assertSame( $post_types, $result );
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

	// ---------------------------------------------------------------------
	// get_registered_block_types()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_registered_block_types_returns_registry_result(): void {

		$blocks = [
			'fundrik/campaign-settings' => Mockery::mock( WP_Block_Type::class ),
		];

		$instance = Mockery::mock( WP_Block_Type_Registry::class );

		$instance
			->shouldReceive( 'get_all_registered' )
			->once()
			->andReturn( $blocks );

		WP_Block_Type_Registry::set_instance( $instance );

		$result = $this->context->get_registered_block_types();

		self::assertSame( $blocks, $result );
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
