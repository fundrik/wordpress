<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\MetaReader;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use UnexpectedValueException;

#[CoversClass( MetaReader::class )]
final class MetaReaderTest extends WordPressTestCase {

	// ---------------------------------------------------------------------
	// find_post_meta_bool()
	// ---------------------------------------------------------------------

	#[Test]
	public function find_post_meta_bool_returns_null_when_meta_does_not_exist(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = MetaReader::find_post_meta_bool( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function find_post_meta_bool_converts_wordpress_false_to_false(): void {

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

		$result = MetaReader::find_post_meta_bool( $post_id, $meta_key );

		self::assertFalse( $result );
	}

	#[Test]
	public function find_post_meta_bool_converts_wordpress_true_to_true(): void {

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

		$result = MetaReader::find_post_meta_bool( $post_id, $meta_key );

		self::assertTrue( $result );
	}

	// ---------------------------------------------------------------------
	// find_post_meta_int()
	// ---------------------------------------------------------------------

	#[Test]
	public function find_post_meta_int_returns_null_when_meta_does_not_exist(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = MetaReader::find_post_meta_int( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function find_post_meta_int_returns_null_when_meta_is_empty_string(): void {

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

		$result = MetaReader::find_post_meta_int( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function find_post_meta_int_converts_numeric_string_to_int(): void {

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

		$result = MetaReader::find_post_meta_int( $post_id, $meta_key );

		self::assertSame( 1500, $result );
	}

	#[Test]
	public function find_post_meta_int_throws_when_the_stored_value_cannot_be_cast_to_integer(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( $post_id, $meta_key, true )
			->andReturn( 'not-an-int' );

		$this->expectException( UnexpectedValueException::class );
		$this->expectExceptionMessage( 'Post meta "fundrik_key" must be int. Given: string.' );

		MetaReader::find_post_meta_int( $post_id, $meta_key );
	}

	// ---------------------------------------------------------------------
	// find_post_meta_string()
	// ---------------------------------------------------------------------

	#[Test]
	public function find_post_meta_string_returns_null_when_meta_does_not_exist(): void {

		$post_id = 123;
		$meta_key = 'fundrik_key';

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', $post_id, $meta_key )
			->andReturn( false );

		Functions\expect( 'get_post_meta' )->never();

		$result = MetaReader::find_post_meta_string( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function find_post_meta_string_returns_null_when_meta_is_empty_string(): void {

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

		$result = MetaReader::find_post_meta_string( $post_id, $meta_key );

		self::assertNull( $result );
	}

	#[Test]
	public function find_post_meta_string_returns_string_value(): void {

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

		$result = MetaReader::find_post_meta_string( $post_id, $meta_key );

		self::assertSame( 'RUB', $result );
	}

}
