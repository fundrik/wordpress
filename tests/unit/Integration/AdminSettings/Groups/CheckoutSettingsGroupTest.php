<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\ActiveGatewaySetting;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CheckoutSettingsGroup::class )]
#[UsesClass( ActiveGatewaySetting::class )]
#[UsesClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( CheckoutCancelUrlSetting::class )]
final class CheckoutSettingsGroupTest extends WordPressTestCase {

	private CheckoutSettingsGroup $settings;

	protected function setUp(): void {

		parent::setUp();

		$this->settings = new CheckoutSettingsGroup(
			new ActiveGatewaySetting(
				new AdminSettingsFieldRenderer(),
				$this->gateway( 'yookassa', 'YooKassa' ),
				$this->gateway( 'astrabank', 'AstraBank' ),
			),
			new CheckoutSuccessUrlSetting( new AdminSettingsFieldRenderer() ),
			new CheckoutCancelUrlSetting( new AdminSettingsFieldRenderer() ),
		);
	}

	#[Test]
	public function it_returns_the_expected_checkout_settings(): void {

		$settings = $this->settings->get_settings();

		self::assertCount( 3, $settings );
		self::assertInstanceOf( ActiveGatewaySetting::class, $settings[0] );
		self::assertSame( 'active_gateway', $settings[0]->get_id() );
		self::assertSame( 'yookassa', $settings[0]->get_default_value() );
		self::assertInstanceOf( CheckoutSuccessUrlSetting::class, $settings[1] );
		self::assertSame( 'success_url', $settings[1]->get_id() );
		self::assertSame( '', $settings[1]->get_default_value() );
		self::assertInstanceOf( CheckoutCancelUrlSetting::class, $settings[2] );
		self::assertSame( 'cancel_url', $settings[2]->get_id() );
		self::assertSame( '', $settings[2]->get_default_value() );
	}

	#[Test]
	public function it_renders_the_section_description(): void {

		ob_start();
		$this->settings->render_section_description();
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'Configure checkout flow settings.', $output );
	}

	private function gateway( string $gateway_id, string $gateway_label ): GatewayInterface {

		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway->shouldReceive( 'get_id' )->andReturn( $gateway_id );
		$gateway->shouldReceive( 'get_label' )->andReturn( $gateway_label );

		return $gateway;
	}
}
