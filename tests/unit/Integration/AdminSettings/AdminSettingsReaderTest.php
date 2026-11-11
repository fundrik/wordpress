<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

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
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\SelectedGatewaySetting;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\FundrikTestCase;
use LogicException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AdminSettingsReader::class )]
#[UsesClass( CampaignDefaultAcceptsDonationsSetting::class )]
#[UsesClass( CampaignDefaultHasTargetSetting::class )]
#[UsesClass( CampaignSettingsGroup::class )]
#[UsesClass( CheckoutCancelUrlSetting::class )]
#[UsesClass( CheckoutSettingsGroup::class )]
#[UsesClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( CurrencySetting::class )]
#[UsesClass( DonationFormDefaultAmountLabelSetting::class )]
#[UsesClass( DonationFormDefaultAmountSetting::class )]
#[UsesClass( DonationFormSettingsGroup::class )]
#[UsesClass( GeneralSettingsGroup::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SelectedGatewaySetting::class )]
#[UsesClass( SettingOptionName::class )]
final class AdminSettingsReaderTest extends FundrikTestCase {

	private AdminSettingsReader $reader;
	private FakeStorage $storage;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->storage = new FakeStorage();
		$this->reader = $this->make_reader();
	}

	#[Test]
	public function constructor_throws_when_setting_class_is_registered_twice(): void {

		$group = new GeneralSettingsGroup( new CurrencySetting() );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage(
			sprintf( 'Admin setting "%s" was registered twice.', CurrencySetting::class ),
		);

		new AdminSettingsReader( new OptionReader( $this->storage ), $group, $group );
	}

	#[Test]
	public function get_currency_returns_configured_value(): void {

		$this->storage->set( 'fundrik_general_currency_setting', 'USD' );

		self::assertSame( 'USD', $this->reader->get_currency() );
	}

	#[Test]
	public function get_currency_returns_default_when_option_is_missing(): void {

		self::assertSame( 'RUB', $this->reader->get_currency() );
	}

	#[Test]
	public function get_currency_returns_default_when_option_has_invalid_type(): void {

		$this->storage->set( 'fundrik_general_currency_setting', false );

		self::assertSame( 'RUB', $this->reader->get_currency() );
	}

	#[Test]
	public function get_selected_gateway_id_returns_configured_value(): void {

		$this->storage->set( 'fundrik_checkout_selected_gateway_setting', 'yookassa' );

		self::assertSame( 'yookassa', $this->reader->get_selected_gateway_id() );
	}

	#[Test]
	public function get_success_url_returns_configured_value(): void {

		$this->storage->set( 'fundrik_checkout_success_url_setting', 'https://example.com/success' );

		self::assertSame( 'https://example.com/success', $this->reader->get_success_url() );
	}

	#[Test]
	public function get_cancel_url_returns_configured_value(): void {

		$this->storage->set( 'fundrik_checkout_cancel_url_setting', 'https://example.com/cancel' );

		self::assertSame( 'https://example.com/cancel', $this->reader->get_cancel_url() );
	}

	#[Test]
	public function get_campaign_default_accepts_donations_returns_configured_value(): void {

		$this->storage->set( 'fundrik_campaign_default_accepts_donations_setting', '0' );

		self::assertFalse( $this->reader->get_campaign_default_accepts_donations() );
	}

	#[Test]
	public function get_campaign_default_accepts_donations_returns_default_when_option_is_missing(): void {

		self::assertTrue( $this->reader->get_campaign_default_accepts_donations() );
	}

	#[Test]
	public function get_campaign_default_accepts_donations_returns_default_when_option_has_invalid_type(): void {

		$this->storage->set( 'fundrik_campaign_default_accepts_donations_setting', [] );

		self::assertTrue( $this->reader->get_campaign_default_accepts_donations() );
	}

	#[Test]
	public function get_campaign_default_has_target_returns_configured_value(): void {

		$this->storage->set( 'fundrik_campaign_default_has_target_setting', '1' );

		self::assertTrue( $this->reader->get_campaign_default_has_target() );
	}

	#[Test]
	public function get_donation_form_default_amount_returns_configured_value(): void {

		$this->storage->set( 'fundrik_donation_form_default_amount_setting', '35' );

		self::assertSame( 35, $this->reader->get_donation_form_default_amount() );
	}

	#[Test]
	public function get_donation_form_default_amount_returns_default_when_option_is_missing(): void {

		self::assertSame( 10, $this->reader->get_donation_form_default_amount() );
	}

	#[Test]
	public function get_donation_form_default_amount_returns_default_when_option_has_invalid_type(): void {

		$this->storage->set( 'fundrik_donation_form_default_amount_setting', false );

		self::assertSame( 10, $this->reader->get_donation_form_default_amount() );
	}

	#[Test]
	public function get_donation_form_default_amount_label_returns_configured_value(): void {

		$this->storage->set( 'fundrik_donation_form_default_amount_label_setting', 'Contribution' );

		self::assertSame( 'Contribution', $this->reader->get_donation_form_default_amount_label() );
	}

	private function make_reader(): AdminSettingsReader {

		return new AdminSettingsReader(
			new OptionReader( $this->storage ),
			new GeneralSettingsGroup( new CurrencySetting() ),
			new CheckoutSettingsGroup(
				new SelectedGatewaySetting(),
				new CheckoutSuccessUrlSetting(),
				new CheckoutCancelUrlSetting(),
			),
			new CampaignSettingsGroup(
				new CampaignDefaultAcceptsDonationsSetting(),
				new CampaignDefaultHasTargetSetting(),
			),
			new DonationFormSettingsGroup(
				new DonationFormDefaultAmountSetting(),
				new DonationFormDefaultAmountLabelSetting(),
			),
		);
	}
}
