<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminSettingsGroupRegistrar::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( AdminSettingInterface::class )]
final class AdminSettingsGroupRegistrarTest extends WordPressTestCase {

	#[Test]
	public function it_registers_all_admin_settings_groups(): void {

		$first_settings_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$first_settings_group->shouldReceive( 'get_option_name' )->once()->andReturn( 'fundrik_general_settings' );
		$first_settings_group->shouldReceive( 'get_section_title' )->once()->andReturn( 'General' );

		$currency_setting = Mockery::mock( AdminSettingInterface::class );
		$currency_setting->shouldReceive( 'get_key' )->andReturn( 'currency' );
		$currency_setting->shouldReceive( 'get_default_value' )->once()->andReturn( 'RUB' );
		$currency_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Currency' );
		$currency_setting->shouldReceive( 'normalize_value' )->once()->andReturnNull();
		$currency_setting->shouldReceive( 'sanitize_value' )->never();
		$first_settings_group->shouldReceive( 'get_settings' )->once()->andReturn( [ $currency_setting ] );

		$second_settings_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$second_settings_group->shouldReceive( 'get_option_name' )->once()->andReturn( 'fundrik_donation_form_settings' );
		$second_settings_group->shouldReceive( 'get_section_title' )->once()->andReturn( 'Donation Form' );

		$amount_setting = Mockery::mock( AdminSettingInterface::class );
		$amount_setting->shouldReceive( 'get_key' )->andReturn( 'default_amount' );
		$amount_setting->shouldReceive( 'get_default_value' )->once()->andReturn( 10 );
		$amount_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Default donation amount' );
		$amount_setting->shouldReceive( 'normalize_value' )->once()->andReturnNull();
		$amount_setting->shouldReceive( 'sanitize_value' )->never();
		$second_settings_group->shouldReceive( 'get_settings' )->once()->andReturn( [ $amount_setting ] );

		Functions\expect( 'get_option' )->twice()->andReturn( [] );
		Functions\expect( 'register_setting' )->twice()->andReturnTrue();
		Functions\expect( 'add_settings_section' )->twice()->andReturnTrue();
		Functions\expect( 'add_settings_field' )->twice()->andReturnTrue();

		$registrar = new AdminSettingsGroupRegistrar( $first_settings_group, $second_settings_group );

		$registrar->register_all();
	}

	#[Test]
	public function it_returns_the_count_of_configured_admin_settings_groups(): void {

		$registrar = new AdminSettingsGroupRegistrar(
			Mockery::mock( AdminSettingsGroupInterface::class ),
			Mockery::mock( AdminSettingsGroupInterface::class ),
		);

		$this->assertSame( 2, $registrar->count() );
	}
}
