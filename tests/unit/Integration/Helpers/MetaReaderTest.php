<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Helpers\MetaReader;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use UnexpectedValueException;

#[CoversClass( MetaReader::class )]
final class MetaReaderTest extends WordPressTestCase {

	private const int POST_ID = 123;
	private const string META_KEY = 'fundrik_key';

	#[Test]
	public function find_post_meta_bool_returns_null_when_meta_does_not_exist(): void {

		$this->expect_missing_meta();

		self::assertNull( MetaReader::find_post_meta_bool( self::POST_ID, self::META_KEY ) );
	}

	#[Test]
	public function find_post_meta_bool_returns_false_for_wordpress_false_value(): void {

		$this->expect_stored_meta_value( '' );

		self::assertFalse( MetaReader::find_post_meta_bool( self::POST_ID, self::META_KEY ) );
	}

	#[Test]
	public function find_post_meta_bool_returns_true_for_wordpress_true_value(): void {

		$this->expect_stored_meta_value( '1' );

		self::assertTrue( MetaReader::find_post_meta_bool( self::POST_ID, self::META_KEY ) );
	}

	#[Test]
	public function find_post_meta_bool_wraps_cast_failure_with_meta_context(): void {

		$this->expect_stored_meta_value( 'yes' );

		try {
			MetaReader::find_post_meta_bool( self::POST_ID, self::META_KEY );
			self::fail( 'Expected an invalid meta value to throw.' );
		} catch ( UnexpectedValueException $exception ) {
			self::assertSame(
				'Post meta "fundrik_key" must be bool. Given: string.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidArgumentException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_post_meta_int_returns_null_when_meta_does_not_exist(): void {

		$this->expect_missing_meta();

		self::assertNull( MetaReader::find_post_meta_int( self::POST_ID, self::META_KEY ) );
	}

	#[Test]
	public function find_post_meta_int_converts_decimal_string_to_int(): void {

		$this->expect_stored_meta_value( '1500' );

		self::assertSame( 1_500, MetaReader::find_post_meta_int( self::POST_ID, self::META_KEY ) );
	}

	#[Test]
	public function find_post_meta_int_wraps_cast_failure_with_meta_context(): void {

		$this->expect_stored_meta_value( '' );

		$this->expectException( UnexpectedValueException::class );
		$this->expectExceptionMessage( 'Post meta "fundrik_key" must be int. Given: string.' );

		MetaReader::find_post_meta_int( self::POST_ID, self::META_KEY );
	}

	#[Test]
	public function find_post_meta_string_returns_null_when_meta_does_not_exist(): void {

		$this->expect_missing_meta();

		self::assertNull( MetaReader::find_post_meta_string( self::POST_ID, self::META_KEY ) );
	}

	#[Test]
	public function find_post_meta_string_returns_stored_string(): void {

		$this->expect_stored_meta_value( 'RUB' );

		self::assertSame( 'RUB', MetaReader::find_post_meta_string( self::POST_ID, self::META_KEY ) );
	}

	#[Test]
	public function find_post_meta_string_wraps_cast_failure_with_meta_context(): void {

		$this->expect_stored_meta_value( 42 );

		$this->expectException( UnexpectedValueException::class );
		$this->expectExceptionMessage( 'Post meta "fundrik_key" must be string. Given: int.' );

		MetaReader::find_post_meta_string( self::POST_ID, self::META_KEY );
	}

	private function expect_missing_meta(): void {

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', self::POST_ID, self::META_KEY )
			->andReturn( false );
	}

	private function expect_stored_meta_value( int|string $value ): void {

		Functions\expect( 'metadata_exists' )
			->once()
			->with( 'post', self::POST_ID, self::META_KEY )
			->andReturn( true );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( self::POST_ID, self::META_KEY, true )
			->andReturn( $value );
	}
}
