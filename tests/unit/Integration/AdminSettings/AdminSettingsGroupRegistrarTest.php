<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AdminSettingsGroupRegistrar::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( AdminSettingInterface::class )]
final class AdminSettingsGroupRegistrarTest extends WordPressTestCase {

	#[Test]
	public function it_registers_all_admin_settings_groups(): void {

		$first_settings_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$first_settings_group->shouldReceive( 'get_id' )->twice()->andReturn( 'general' );
		$first_settings_group->shouldReceive( 'get_section_title' )->once()->andReturn( 'General' );

		$currency_setting = Mockery::mock( AdminSettingInterface::class );
		$currency_setting->shouldReceive( 'get_id' )->andReturn( 'currency' );
		$currency_setting->shouldReceive( 'get_default_value' )->andReturn( 'RUB' );
		$currency_setting->shouldReceive( 'get_value_type' )->andReturn( WpSchemaType::String );
		$currency_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Currency' );
		$currency_setting->shouldReceive( 'sanitize_value' )->never();
		$first_settings_group->shouldReceive( 'get_settings' )->once()->andReturn( [ $currency_setting ] );

		$second_settings_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$second_settings_group->shouldReceive( 'get_id' )->times( 3 )->andReturn( 'donation_form' );
		$second_settings_group->shouldReceive( 'get_section_title' )->once()->andReturn( 'Donation Form' );

		$amount_setting = Mockery::mock( AdminSettingInterface::class );
		$amount_setting->shouldReceive( 'get_id' )->andReturn( 'default_amount' );
		$amount_setting->shouldReceive( 'get_default_value' )->andReturn( 10 );
		$amount_setting->shouldReceive( 'get_value_type' )->andReturn( WpSchemaType::Integer );
		$amount_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Default donation amount' );
		$amount_setting->shouldReceive( 'sanitize_value' )->never();

		$amount_label_setting = Mockery::mock( AdminSettingInterface::class );
		$amount_label_setting->shouldReceive( 'get_id' )->andReturn( 'default_amount_label' );
		$amount_label_setting->shouldReceive( 'get_default_value' )->andReturn( 'Amount' );
		$amount_label_setting->shouldReceive( 'get_value_type' )->andReturn( WpSchemaType::String );
		$amount_label_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Default amount label' );
		$amount_label_setting->shouldReceive( 'sanitize_value' )->never();

		$second_settings_group->shouldReceive( 'get_settings' )->once()->andReturn(
			[
				$amount_setting,
				$amount_label_setting,
			],
		);

		Functions\expect( 'get_option' )
			->once()
			->with( 'fundrik_general_currency_setting', 'RUB' )
			->andReturn( 'USD' );
		Functions\expect( 'get_option' )
			->once()
			->with( 'fundrik_donation_form_default_amount_setting', 10 )
			->andReturn( 10 );
		Functions\expect( 'get_option' )
			->once()
			->with( 'fundrik_donation_form_default_amount_label_setting', 'Amount' )
			->andReturn( 'Amount' );
		Functions\expect( 'register_setting' )->times( 3 )->andReturnTrue();
		Functions\expect( 'add_settings_section' )->twice()->andReturnTrue();
		Functions\expect( 'add_settings_field' )->times( 3 )->andReturnTrue();

		$registrar = new AdminSettingsGroupRegistrar( $first_settings_group, $second_settings_group );

		$registrar->register_all();
	}

	#[Test]
	public function it_keeps_the_current_setting_value_when_sanitization_fails(): void {

		$settings_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$settings_group->shouldReceive( 'get_id' )->twice()->andReturn( 'general' );
		$settings_group->shouldReceive( 'get_section_title' )->once()->andReturn( 'General' );

		$currency_setting = Mockery::mock( AdminSettingInterface::class );
		$currency_setting->shouldReceive( 'get_id' )->andReturn( 'currency' );
		$currency_setting->shouldReceive( 'get_default_value' )->andReturn( 'RUB' );
		$currency_setting->shouldReceive( 'get_value_type' )->andReturn( WpSchemaType::String );
		$currency_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Currency' );
		$currency_setting->shouldReceive( 'sanitize_value' )->once()->andThrow(
			new \InvalidArgumentException( 'Currency must be a 3-letter ISO 4217 code. Given: bool.' ),
		);
		$settings_group->shouldReceive( 'get_settings' )->once()->andReturn( [ $currency_setting ] );

		Functions\expect( 'get_option' )
			->once()
			->with( 'fundrik_general_currency_setting', 'RUB' )
			->andReturn( 'USD' );
		Functions\expect( 'register_setting' )
			->once()
			->andReturnUsing(
				static function ( string $page, string $option_name, array $args ): bool {

					self::assertSame( WpSchemaType::String->value, $args['type'] );

					$result = $args['sanitize_callback']( false );

					self::assertSame( 'USD', $result );

					return true;
				}
			);
		Functions\expect( 'add_settings_section' )->once()->andReturnTrue();
		Functions\expect( 'add_settings_field' )->once()->andReturnTrue();
		Functions\expect( 'add_settings_error' )
			->once()
			->with(
				'fundrik_general_currency_setting',
				'fundrik_general_currency_setting_invalid',
				'Currency must be a 3-letter ISO 4217 code. Given: bool.',
			);

		$registrar = new AdminSettingsGroupRegistrar( $settings_group );

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
