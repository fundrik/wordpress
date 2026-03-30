<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\CurrencySetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DefaultDonationAmountSetting;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AdminSettingsReader::class )]
#[UsesClass( CurrencySetting::class )]
#[UsesClass( DefaultDonationAmountSetting::class )]
#[UsesClass( DefaultAmountLabelSetting::class )]
#[UsesClass( GeneralSettingsGroup::class )]
#[UsesClass( DonationFormSettingsGroup::class )]
final class AdminSettingsReaderTest extends WordPressTestCase {

	#[Test]
	public function it_returns_the_configured_currency_value(): void {

		Functions\expect( 'get_option' )
			->once()
			->with(
				'fundrik_general_settings',
				[
					CurrencySetting::KEY => CurrencySetting::DEFAULT_VALUE,
				],
			)
			->andReturn(
				[
					CurrencySetting::KEY => 'usd',
				],
			);

		$reader = new AdminSettingsReader(
			new GeneralSettingsGroup( new CurrencySetting( new AdminSettingsFieldRenderer() ) ),
			new DonationFormSettingsGroup(
				new DefaultDonationAmountSetting( new AdminSettingsFieldRenderer() ),
				new DefaultAmountLabelSetting( new AdminSettingsFieldRenderer() ),
			),
		);

		self::assertSame( 'USD', $reader->get( CurrencySetting::class ) );
	}

	#[Test]
	public function it_returns_the_configured_donation_form_defaults(): void {

		Functions\expect( 'get_option' )
			->once()
			->with(
				'fundrik_donation_form_settings',
				[
					DefaultDonationAmountSetting::KEY => DefaultDonationAmountSetting::DEFAULT_VALUE,
					DefaultAmountLabelSetting::KEY => DefaultAmountLabelSetting::DEFAULT_VALUE,
				],
			)
			->andReturn(
				[
					DefaultDonationAmountSetting::KEY => '35',
					DefaultAmountLabelSetting::KEY => 'Contribution',
				],
			);

		$reader = new AdminSettingsReader(
			new GeneralSettingsGroup( new CurrencySetting( new AdminSettingsFieldRenderer() ) ),
			new DonationFormSettingsGroup(
				new DefaultDonationAmountSetting( new AdminSettingsFieldRenderer() ),
				new DefaultAmountLabelSetting( new AdminSettingsFieldRenderer() ),
			),
		);

		self::assertSame( 35, $reader->get( DefaultDonationAmountSetting::class ) );
		self::assertSame( 'Contribution', $reader->get( DefaultAmountLabelSetting::class ) );
	}

	#[Test]
	public function it_falls_back_to_defaults_for_invalid_donation_form_values(): void {

		Functions\expect( 'get_option' )
			->once()
			->with(
				'fundrik_donation_form_settings',
				[
					DefaultDonationAmountSetting::KEY => DefaultDonationAmountSetting::DEFAULT_VALUE,
					DefaultAmountLabelSetting::KEY => DefaultAmountLabelSetting::DEFAULT_VALUE,
				],
			)
			->andReturn(
				[
					DefaultDonationAmountSetting::KEY => '0',
					DefaultAmountLabelSetting::KEY => '  ',
				],
			);

		$reader = new AdminSettingsReader(
			new GeneralSettingsGroup( new CurrencySetting( new AdminSettingsFieldRenderer() ) ),
			new DonationFormSettingsGroup(
				new DefaultDonationAmountSetting( new AdminSettingsFieldRenderer() ),
				new DefaultAmountLabelSetting( new AdminSettingsFieldRenderer() ),
			),
		);

		self::assertSame( DefaultDonationAmountSetting::DEFAULT_VALUE, $reader->get( DefaultDonationAmountSetting::class ) );
		self::assertSame( DefaultAmountLabelSetting::DEFAULT_VALUE, $reader->get( DefaultAmountLabelSetting::class ) );
	}
}
