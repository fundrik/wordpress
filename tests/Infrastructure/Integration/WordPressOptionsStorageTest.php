<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Integration\WordPressOptionsStorage;
use Fundrik\WordPress\Infrastructure\StorageInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( WordPressOptionsStorage::class )]
final class WordPressOptionsStorageTest extends MockeryTestCase {

	private WordPressOptionsStorage $storage;

	protected function setUp(): void {

		parent::setUp();

		$this->storage = new WordPressOptionsStorage();
	}

	#[Test]
	public function it_implements_storage_interface(): void {

		self::assertInstanceOf( StorageInterface::class, $this->storage );
	}

	#[Test]
	public function get_calls_get_option_with_only_key_when_default_is_not_provided(): void {

		Functions\expect( 'get_option' )
			->once()
			->with( 'fundrik_option_key' )
			->andReturn( 'stored-value' );

		$returned = $this->storage->get( 'fundrik_option_key' );

		self::assertSame( 'stored-value', $returned );
	}

	#[Test]
	public function get_calls_get_option_with_key_and_default_when_default_is_provided(): void {

		Functions\expect( 'get_option' )
			->once()
			->with( 'fundrik_option_key', 'fallback' )
			->andReturn( 'fallback' );

		$returned = $this->storage->get( 'fundrik_option_key', 'fallback' );

		self::assertSame( 'fallback', $returned );
	}

	#[Test]
	public function get_calls_get_option_with_key_and_null_when_null_default_is_provided_explicitly(): void {

		Functions\expect( 'get_option' )
			->once()
			->with( 'fundrik_option_key', null )
			->andReturn( null );

		$returned = $this->storage->get( 'fundrik_option_key', null );

		self::assertNull( $returned );
	}

	#[Test]
	public function set_calls_update_option_and_returns_true_on_success(): void {

		Functions\expect( 'update_option' )
			->once()
			->with( 'fundrik_option_key', 'new-value' )
			->andReturn( true );

		$returned = $this->storage->set( 'fundrik_option_key', 'new-value' );

		self::assertTrue( $returned );
	}

	#[Test]
	public function set_calls_update_option_and_returns_false_on_failure(): void {

		Functions\expect( 'update_option' )
			->once()
			->with( 'fundrik_option_key', 'new-value' )
			->andReturn( false );

		$returned = $this->storage->set( 'fundrik_option_key', 'new-value' );

		self::assertFalse( $returned );
	}
}
