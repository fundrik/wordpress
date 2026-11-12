<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Helpers;

use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\FundrikTestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use UnexpectedValueException;

#[CoversClass( OptionReader::class )]
final class OptionReaderTest extends FundrikTestCase {

	private const string OPTION_NAME = 'fundrik_option_key';

	private FakeStorage $storage;
	private OptionReader $reader;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->storage = new FakeStorage();
		$this->reader = new OptionReader( $this->storage );
	}

	#[Test]
	public function find_int_option_returns_null_when_option_does_not_exist(): void {

		self::assertNull( $this->reader->find_int_option( self::OPTION_NAME ) );
	}

	#[Test]
	public function find_int_option_converts_decimal_string_to_int(): void {

		$this->storage->set( self::OPTION_NAME, '35' );

		self::assertSame( 35, $this->reader->find_int_option( self::OPTION_NAME ) );
	}

	#[Test]
	public function find_int_option_wraps_cast_failure_with_option_context(): void {

		$this->storage->set( self::OPTION_NAME, '' );

		try {
			$this->reader->find_int_option( self::OPTION_NAME );
			self::fail( 'Expected an invalid option value to throw.' );
		} catch ( UnexpectedValueException $exception ) {
			self::assertSame(
				'Option "fundrik_option_key" must be int. Given: string.',
				$exception->getMessage(),
			);
			self::assertInstanceOf( InvalidArgumentException::class, $exception->getPrevious() );
		}
	}

	#[Test]
	public function find_string_option_returns_null_when_option_does_not_exist(): void {

		self::assertNull( $this->reader->find_string_option( self::OPTION_NAME ) );
	}

	#[Test]
	public function find_string_option_returns_stored_string(): void {

		$this->storage->set( self::OPTION_NAME, 'stored-value' );

		self::assertSame( 'stored-value', $this->reader->find_string_option( self::OPTION_NAME ) );
	}

	#[Test]
	public function find_string_option_wraps_cast_failure_with_option_context(): void {

		$this->storage->set( self::OPTION_NAME, false );

		$this->expectException( UnexpectedValueException::class );
		$this->expectExceptionMessage( 'Option "fundrik_option_key" must be string. Given: bool.' );

		$this->reader->find_string_option( self::OPTION_NAME );
	}

	#[Test]
	public function find_bool_option_returns_null_when_option_does_not_exist(): void {

		self::assertNull( $this->reader->find_bool_option( self::OPTION_NAME ) );
	}

	#[Test]
	public function find_bool_option_converts_binary_string_to_bool(): void {

		$this->storage->set( self::OPTION_NAME, '0' );

		self::assertFalse( $this->reader->find_bool_option( self::OPTION_NAME ) );
	}

	#[Test]
	public function find_bool_option_wraps_cast_failure_with_option_context(): void {

		$this->storage->set( self::OPTION_NAME, 'yes' );

		$this->expectException( UnexpectedValueException::class );
		$this->expectExceptionMessage( 'Option "fundrik_option_key" must be bool. Given: string.' );

		$this->reader->find_bool_option( self::OPTION_NAME );
	}
}
