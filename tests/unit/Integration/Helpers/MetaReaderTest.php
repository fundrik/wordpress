<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\MetaReader;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( MetaReader::class )]
final class MetaReaderTest extends WordPressTestCase {

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

		$result = MetaReader::get_post_meta_or_null( $post_id, $meta_key );

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

		$result = MetaReader::get_post_meta_or_null( $post_id, $meta_key );

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

		$result = MetaReader::get_post_meta_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	// ---------------------------------------------------------------------
	// get_post_meta_bool_or_null()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_post_meta_bool_or_null_returns_null_when_meta_does_not_exist(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = MetaReader::get_post_meta_bool_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function get_post_meta_bool_or_null_converts_wordpress_false_to_false(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( '' );

		$result = MetaReader::get_post_meta_bool_or_null( $post_id, $meta_key );

		self::assertFalse( $result );
	}

	#[Test]
	public function get_post_meta_bool_or_null_converts_wordpress_true_to_true(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( '1' );

		$result = MetaReader::get_post_meta_bool_or_null( $post_id, $meta_key );

		self::assertTrue( $result );
	}

	// ---------------------------------------------------------------------
	// get_post_meta_int_or_null()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_post_meta_int_or_null_returns_null_when_meta_does_not_exist(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = MetaReader::get_post_meta_int_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function get_post_meta_int_or_null_returns_null_when_meta_is_empty_string(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( '' );

		$result = MetaReader::get_post_meta_int_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function get_post_meta_int_or_null_converts_numeric_string_to_int(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( '1500' );

		$result = MetaReader::get_post_meta_int_or_null( $post_id, $meta_key );

		self::assertSame( 1500, $result );
	}

	// ---------------------------------------------------------------------
	// get_post_meta_string_or_null()
	// ---------------------------------------------------------------------

	#[Test]
	public function get_post_meta_string_or_null_returns_null_when_meta_does_not_exist(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = MetaReader::get_post_meta_string_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function get_post_meta_string_or_null_returns_null_when_meta_is_empty_string(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( '' );

		$result = MetaReader::get_post_meta_string_or_null( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function get_post_meta_string_or_null_returns_string_value(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( 'RUB' );

		$result = MetaReader::get_post_meta_string_or_null( $post_id, $meta_key );

		self::assertSame( 'RUB', $result );
	}

}
