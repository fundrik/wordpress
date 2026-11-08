<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingRegistration;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AdminSettingRegistration::class )]
#[UsesClass( CurrencySetting::class )]
final class AdminSettingRegistrationTest extends FundrikTestCase {

	#[Test]
	public function constructor_stores_registration_data(): void {

		$setting = new CurrencySetting();
		$registration = new AdminSettingRegistration(
			group_id: 'general',
			option_name: 'fundrik_general_currency_setting',
			setting: $setting,
			current_value: 'USD',
		);

		self::assertSame( 'general', $registration->group_id );
		self::assertSame( 'fundrik_general_currency_setting', $registration->option_name );
		self::assertSame( $setting, $registration->setting );
		self::assertSame( 'USD', $registration->current_value );
	}
}
