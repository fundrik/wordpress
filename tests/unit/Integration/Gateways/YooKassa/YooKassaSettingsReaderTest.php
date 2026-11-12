<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways\YooKassa;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaSettingsReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRoute;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( YooKassaSettingsReader::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( RestRouteDefinitions::class )]
#[UsesClass( SettingOptionName::class )]
#[UsesClass( YooKassaNotifyRestRoute::class )]
final class YooKassaSettingsReaderTest extends WordPressTestCase {

	private YooKassaSettingsReader $reader;
	private FakeStorage $storage;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->storage = new FakeStorage();
		$this->reader = new YooKassaSettingsReader( new OptionReader( $this->storage ) );
	}

	#[Test]
	public function get_shop_id_returns_configured_value(): void {

		$this->storage->set( 'fundrik_yookassa_shop_id_setting', 'shop-123' );

		self::assertSame( 'shop-123', $this->reader->get_shop_id() );
	}

	#[Test]
	public function get_shop_id_returns_empty_string_when_option_is_missing(): void {

		self::assertSame( '', $this->reader->get_shop_id() );
	}

	#[Test]
	public function get_shop_id_returns_empty_string_when_option_has_invalid_type(): void {

		$this->storage->set( 'fundrik_yookassa_shop_id_setting', false );

		self::assertSame( '', $this->reader->get_shop_id() );
	}

	#[Test]
	public function get_secret_key_returns_configured_value(): void {

		$this->storage->set( 'fundrik_yookassa_shop_id_setting', 'shop-123' );
		$this->storage->set( 'fundrik_yookassa_secret_key_setting', 'secret-key' );

		self::assertSame( 'secret-key', $this->reader->get_secret_key() );
	}

	#[Test]
	public function get_webhook_url_returns_yookassa_notification_route_url(): void {

		Functions\expect( 'rest_url' )
			->once()
			->with( 'fundrik/v1/gateways/yookassa/notify' )
			->andReturn( 'https://example.test/wp-json/fundrik/v1/gateways/yookassa/notify' );

		self::assertSame(
			'https://example.test/wp-json/fundrik/v1/gateways/yookassa/notify',
			$this->reader->get_webhook_url(),
		);
	}
}
