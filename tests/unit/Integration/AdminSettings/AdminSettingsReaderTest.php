<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\ActiveGatewaySetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Tests\Fixtures\FakeStorageNotFoundException;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AdminSettingsReader::class )]
#[UsesClass( CurrencySetting::class )]
#[UsesClass( ActiveGatewaySetting::class )]
#[UsesClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( CheckoutCancelUrlSetting::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
#[UsesClass( DonationFormDefaultAmountSetting::class )]
#[UsesClass( DonationFormDefaultAmountLabelSetting::class )]
#[UsesClass( GeneralSettingsGroup::class )]
#[UsesClass( CheckoutSettingsGroup::class )]
#[UsesClass( CampaignSettingsGroup::class )]
#[UsesClass( DonationFormSettingsGroup::class )]
final class AdminSettingsReaderTest extends WordPressTestCase {

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
			->with( 'fundrik_general_currency_setting' )
			->andReturn( 'USD' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_checkout_active_gateway_setting' )
			->andReturn( 'astrabank' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_checkout_success_url_setting' )
			->andReturn( 'https://example.com/success' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_checkout_cancel_url_setting' )
			->andReturn( 'https://example.com/cancel' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_campaign_default_accepts_donations_setting' )
			->andReturn( true );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_campaign_default_has_target_setting' )
			->andReturn( false );

		$reader = $this->create_reader(
			$this->gateway( 'astrabank', 'AstraBank' ),
			$this->gateway( 'yookassa', 'YooKassa' ),
		);

		self::assertSame( 'USD', $reader->get_currency() );
		self::assertSame( 'astrabank', $reader->get_active_gateway_id() );
		self::assertSame( 'https://example.com/success', $reader->get_success_url() );
		self::assertSame( 'https://example.com/cancel', $reader->get_cancel_url() );
		self::assertTrue( $reader->get_campaign_default_accepts_donations() );
		self::assertFalse( $reader->get_campaign_default_has_target() );
	}

	#[Test]
	public function it_returns_the_configured_donation_form_defaults(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_donation_form_default_amount_setting' )
			->andReturn( 35 );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_donation_form_default_amount_label_setting' )
			->andReturn( 'Contribution' );

		$reader = $this->create_reader(
			$this->gateway( 'astrabank', 'AstraBank' ),
			$this->gateway( 'yookassa', 'YooKassa' ),
		);

		self::assertSame( 35, $reader->get_donation_form_default_amount() );
		self::assertSame( 'Contribution', $reader->get_donation_form_default_amount_label() );
	}

	#[Test]
	public function it_falls_back_to_defaults_for_invalid_donation_form_values(): void {

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_donation_form_default_amount_setting' )
			->andReturn( [] );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_donation_form_default_amount_label_setting' )
			->andReturn( false );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_campaign_default_accepts_donations_setting' )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_campaign_default_has_target_setting' )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		$reader = $this->create_reader(
			$this->gateway( 'astrabank', 'AstraBank' ),
			$this->gateway( 'yookassa', 'YooKassa' ),
		);

		$default_donation_amount_setting = new DonationFormDefaultAmountSetting( new AdminSettingsFieldRenderer() );
		$default_amount_label_setting = new DonationFormDefaultAmountLabelSetting( new AdminSettingsFieldRenderer() );

		self::assertSame( $default_donation_amount_setting->get_default_value(), $reader->get_donation_form_default_amount() );
		self::assertSame( $default_amount_label_setting->get_default_value(), $reader->get_donation_form_default_amount_label() );
		self::assertTrue( $reader->get_campaign_default_accepts_donations() );
		self::assertFalse( $reader->get_campaign_default_has_target() );
	}

	private function create_reader( GatewayInterface ...$gateways ): AdminSettingsReader {

		return new AdminSettingsReader(
			new OptionReader( $this->storage ),
			new GeneralSettingsGroup( new CurrencySetting( new AdminSettingsFieldRenderer() ) ),
			new CheckoutSettingsGroup(
				new ActiveGatewaySetting( new AdminSettingsFieldRenderer(), ...$gateways ),
				new CheckoutSuccessUrlSetting( new AdminSettingsFieldRenderer() ),
				new CheckoutCancelUrlSetting( new AdminSettingsFieldRenderer() ),
			),
			new CampaignSettingsGroup(
				new CampaignDefaultAcceptsDonationsSetting( new AdminSettingsFieldRenderer() ),
				new CampaignDefaultHasTargetSetting( new AdminSettingsFieldRenderer() ),
			),
			new DonationFormSettingsGroup(
				new DonationFormDefaultAmountSetting( new AdminSettingsFieldRenderer() ),
				new DonationFormDefaultAmountLabelSetting( new AdminSettingsFieldRenderer() ),
			),
		);
	}

	private function gateway( string $gateway_id, string $gateway_label ): GatewayInterface {

		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway->shouldReceive( 'get_id' )->andReturn( $gateway_id );
		$gateway->shouldReceive( 'get_label' )->andReturn( $gateway_label );

		return $gateway;
	}
}
