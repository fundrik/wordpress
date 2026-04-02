<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\Storage\WordPressOptionNotFoundException;
use Fundrik\WordPress\Integration\Storage\WordPressOptionsStorage;
use Fundrik\WordPress\Integration\Storage\WordPressOptionsStorageException;
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

		self::assertInstanceOf( StoragePort::class, $this->storage );
	}

	#[Test]
	public function get_returns_the_stored_option_value(): void {

		Functions\expect( 'get_option' )
			->once()
			->withArgs(
				static fn ( string $key, mixed $default ): bool => 'fundrik_option_key' === $key,
			)
			->andReturn( 'stored-value' );

		$returned = $this->storage->get( 'fundrik_option_key' );

		self::assertSame( 'stored-value', $returned );
	}

	#[Test]
	public function get_throws_when_the_option_does_not_exist(): void {

		Functions\expect( 'get_option' )
			->once()
			->withArgs(
				static fn ( string $key, mixed $default ): bool => 'fundrik_option_key' === $key,
			)
			->andReturnUsing(
				static fn ( string $key, mixed $default ): mixed => $default,
			);

		$this->expectException( WordPressOptionNotFoundException::class );
		$this->expectExceptionMessage( 'Cannot read option "fundrik_option_key": option not found.' );

		$this->storage->get( 'fundrik_option_key' );
	}

	#[Test]
	public function set_stores_the_option_value(): void {

		Functions\expect( 'get_option' )
			->once()
			->withArgs(
				static fn ( string $key, mixed $default ): bool => 'fundrik_option_key' === $key,
			)
			->andReturn( 'old-value' );
		Functions\expect( 'update_option' )
			->once()
			->with( 'fundrik_option_key', 'new-value' )
			->andReturn( true );

		$this->storage->set( 'fundrik_option_key', 'new-value' );
		$this->addToAssertionCount( 1 );
	}

	#[Test]
	public function set_treats_an_unchanged_option_value_as_success(): void {

		Functions\expect( 'get_option' )
			->once()
			->withArgs(
				static fn ( string $key, mixed $default ): bool => 'fundrik_option_key' === $key,
			)
			->andReturn( 'new-value' );
		Functions\expect( 'update_option' )
			->once()
			->with( 'fundrik_option_key', 'new-value' )
			->andReturn( false );

		$this->storage->set( 'fundrik_option_key', 'new-value' );
		$this->addToAssertionCount( 1 );
	}

	#[Test]
	public function set_throws_when_the_option_cannot_be_stored(): void {

		Functions\expect( 'get_option' )
			->once()
			->withArgs(
				static fn ( string $key, mixed $default ): bool => 'fundrik_option_key' === $key,
			)
			->andReturn( 'old-value' );
		Functions\expect( 'update_option' )
			->once()
			->with( 'fundrik_option_key', 'new-value' )
			->andReturn( false );

		$this->expectException( WordPressOptionsStorageException::class );
		$this->expectExceptionMessage( 'Failed to write option "fundrik_option_key".' );

		$this->storage->set( 'fundrik_option_key', 'new-value' );
	}
}
