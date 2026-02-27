<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\Meta;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( Meta::class )]
final class MetaTest extends WordPressTestCase {

	// ---------------------------------------------------------------------
	// get_post_meta_or_null()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_post_meta_or_null_returns_null_when_meta_does_not_exist(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = Meta::get_post_meta_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function get_post_meta_or_null_returns_value_when_meta_exists(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';
		$stored_value = 'some-value';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( $stored_value );

		$result = Meta::get_post_meta_or_null( $post_id, $meta_key );

		self::assertSame( $stored_value, $result );
	}

	#[Test]
	public function get_post_meta_or_null_returns_null_when_meta_exists_but_value_is_null(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( null );

		$result = Meta::get_post_meta_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	// ---------------------------------------------------------------------
	// normalize_wp_bool_value()
	// ---------------------------------------------------------------------

	#[Test]
	public function normalize_wp_bool_value_converts_empty_string_to_zero_string(): void {

		self::assertSame( '0', Meta::normalize_wp_bool_value( '' ) );
	}

	#[Test]
	public function normalize_wp_bool_value_returns_value_unchanged_when_not_empty(): void {

		self::assertSame( '1', Meta::normalize_wp_bool_value( '1' ) );
		self::assertSame( '0', Meta::normalize_wp_bool_value( '0' ) );
		self::assertSame( 'true', Meta::normalize_wp_bool_value( 'true' ) );
		self::assertSame( 'anything', Meta::normalize_wp_bool_value( 'anything' ) );
	}
}
