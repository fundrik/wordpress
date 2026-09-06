<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways\YooKassa;

use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaSettingsReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( YooKassaSettingsReader::class )]
final class YooKassaSettingsReaderTest extends WordPressTestCase {

	private StoragePort&MockInterface $storage;

	protected function setUp(): void {

		parent::setUp();

		$this->storage = Mockery::mock( StoragePort::class );
	}

	#[Test]
	public function it_returns_the_configured_values(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_enabled_setting' )
			->andReturn( true );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_test_mode_setting' )
			->andReturn( true );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_shop_id_setting' )
			->andReturn( '123456' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_secret_key_setting' )
			->andReturn( 'secret' );
		$reader = new YooKassaSettingsReader( new OptionReader( $this->storage ) );

		self::assertTrue( $reader->get_enabled() );
		self::assertTrue( $reader->get_test_mode() );
		self::assertSame( '123456', $reader->get_shop_id() );
		self::assertSame( 'secret', $reader->get_secret_key() );
	}
}
