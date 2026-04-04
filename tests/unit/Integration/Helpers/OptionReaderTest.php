<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Tests\Fixtures\FakeStorageNotFoundException;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use UnexpectedValueException;

#[CoversClass( OptionReader::class )]
final class OptionReaderTest extends WordPressTestCase {

	private StoragePort&MockInterface $storage;
	private OptionReader $reader;

	protected function setUp(): void {

		parent::setUp();

		$this->storage = Mockery::mock( StoragePort::class );
		$this->reader = new OptionReader( $this->storage );
	}

	#[Test]
	public function find_string_option_returns_the_stored_string_value_when_it_is_a_string(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_option_key' )
			->andReturn( 'stored-value' );

		self::assertSame(
			'stored-value',
			$this->reader->find_string_option( 'fundrik_option_key' ),
		);
	}

	#[Test]
	public function find_int_option_throws_when_the_stored_value_cannot_be_cast_to_integer(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_option_key' )
			->andReturn( 'not-an-int' );

		$this->expectException( UnexpectedValueException::class );

		$this->reader->find_int_option( 'fundrik_option_key' );
	}

	#[Test]
	public function find_int_option_returns_the_cast_integer_value_when_the_stored_value_is_a_numeric_string(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_option_key' )
			->andReturn( '35' );

		self::assertSame(
			35,
			$this->reader->find_int_option( 'fundrik_option_key' ),
		);
	}

	#[Test]
	public function find_int_option_throws_when_the_stored_value_is_an_empty_string(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_option_key' )
			->andReturn( '' );

		$this->expectException( UnexpectedValueException::class );

		$this->reader->find_int_option( 'fundrik_option_key' );
	}

	#[Test]
	public function find_string_option_returns_null_when_the_option_does_not_exist(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_option_key' )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		self::assertNull(
			$this->reader->find_string_option( 'fundrik_option_key' ),
		);
	}

	#[Test]
	public function find_string_option_returns_the_empty_string_when_it_is_stored(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_option_key' )
			->andReturn( '' );

		self::assertSame(
			'',
			$this->reader->find_string_option( 'fundrik_option_key' ),
		);
	}

	#[Test]
	public function find_string_option_throws_when_the_stored_value_cannot_be_cast_to_string(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_option_key' )
			->andReturn( false );

		$this->expectException( UnexpectedValueException::class );

		$this->reader->find_string_option( 'fundrik_option_key' );
	}
}
