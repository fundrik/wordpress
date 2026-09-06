<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\ActiveGatewaySetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\Gateways\GatewayResolver;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Tests\Fixtures\FakeStorageNotFoundException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( GatewayResolver::class )]
final class GatewayResolverTest extends MockeryTestCase {

	private StoragePort&MockInterface $storage;

	protected function setUp(): void {

		parent::setUp();

		$this->storage = Mockery::mock( StoragePort::class );
	}

	#[Test]
	public function it_returns_the_configured_gateway(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_checkout_active_gateway_setting' )
			->andReturn( 'astrabank' );

		$selected_gateway = $this->gateway( 'astrabank' );
		$fallback_gateway = $this->gateway( 'yookassa' );
		$reader = $this->admin_settings_reader( $fallback_gateway, $selected_gateway );

		$resolver = new GatewayResolver(
			$reader,
			$fallback_gateway,
			$selected_gateway,
		);

		self::assertSame( $selected_gateway, $resolver->resolve_active_gateway() );
	}

	#[Test]
	public function it_falls_back_to_the_first_gateway_when_no_active_gateway_is_configured(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_checkout_active_gateway_setting' )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		$fallback_gateway = $this->gateway( 'yookassa' );
		$other_gateway = $this->gateway( 'astrabank' );
		$reader = $this->admin_settings_reader( $fallback_gateway, $other_gateway );

		$resolver = new GatewayResolver(
			$reader,
			$fallback_gateway,
			$other_gateway,
		);

		self::assertSame( $fallback_gateway, $resolver->resolve_active_gateway() );
	}

	private function gateway( string $gateway_id ): GatewayInterface&MockInterface {

		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway
			->shouldReceive( 'get_id' )
			->andReturn( $gateway_id );

		return $gateway;
	}

	private function admin_settings_reader( GatewayInterface ...$gateways ): AdminSettingsReader {

		return new AdminSettingsReader(
			new OptionReader( $this->storage ),
			new GeneralSettingsGroup(
				new CurrencySetting( new AdminSettingsFieldRenderer() ),
			),
			new CheckoutSettingsGroup(
				new ActiveGatewaySetting( new AdminSettingsFieldRenderer(), ...$gateways ),
				new CheckoutSuccessUrlSetting( new AdminSettingsFieldRenderer() ),
				new CheckoutCancelUrlSetting( new AdminSettingsFieldRenderer() ),
			),
		);
	}
}
