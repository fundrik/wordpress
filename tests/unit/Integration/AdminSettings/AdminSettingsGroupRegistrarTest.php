<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AdminSettingsGroupRegistrar::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( AdminSettingInterface::class )]
final class AdminSettingsGroupRegistrarTest extends WordPressTestCase {

	private StoragePort&MockInterface $storage;

	protected function setUp(): void {

		parent::setUp();

		$this->storage = Mockery::mock( StoragePort::class );
	}

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

		$third_settings_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$third_settings_group->shouldReceive( 'get_id' )->times( 3 )->andReturn( 'campaign' );
		$third_settings_group->shouldReceive( 'get_section_title' )->once()->andReturn( 'Campaign' );

		$accepts_donations_setting = Mockery::mock( AdminSettingInterface::class );
		$accepts_donations_setting->shouldReceive( 'get_id' )->andReturn( 'default_accepts_donations' );
		$accepts_donations_setting->shouldReceive( 'get_default_value' )->andReturn( true );
		$accepts_donations_setting->shouldReceive( 'get_value_type' )->andReturn( WpSchemaType::Boolean );
		$accepts_donations_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Default accepts donations' );
		$accepts_donations_setting->shouldReceive( 'sanitize_value' )->never();

		$has_target_setting = Mockery::mock( AdminSettingInterface::class );
		$has_target_setting->shouldReceive( 'get_id' )->andReturn( 'default_has_target' );
		$has_target_setting->shouldReceive( 'get_default_value' )->andReturn( false );
		$has_target_setting->shouldReceive( 'get_value_type' )->andReturn( WpSchemaType::Boolean );
		$has_target_setting->shouldReceive( 'get_label' )->once()->andReturn( 'Default has target' );
		$has_target_setting->shouldReceive( 'sanitize_value' )->never();

		$third_settings_group->shouldReceive( 'get_settings' )->once()->andReturn(
			[
				$accepts_donations_setting,
				$has_target_setting,
			],
		);

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_general_currency_setting' )
			->andReturn( 'USD' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_donation_form_default_amount_setting' )
			->andReturn( 10 );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_donation_form_default_amount_label_setting' )
			->andReturn( 'Amount' );
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
		Functions\expect( 'register_setting' )->times( 5 )->andReturnTrue();
		Functions\expect( 'add_settings_section' )->times( 3 )->andReturnTrue();
		Functions\expect( 'add_settings_field' )->times( 5 )->andReturnTrue();

		$registrar = new AdminSettingsGroupRegistrar(
			new OptionReader( $this->storage ),
			$first_settings_group,
			$second_settings_group,
			$third_settings_group,
		);

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

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_general_currency_setting' )
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

		$registrar = new AdminSettingsGroupRegistrar(
			new OptionReader( $this->storage ),
			$settings_group,
		);

		$registrar->register_all();
	}

	#[Test]
	public function it_returns_the_count_of_configured_admin_settings_groups(): void {

		$registrar = new AdminSettingsGroupRegistrar(
			new OptionReader( $this->storage ),
			Mockery::mock( AdminSettingsGroupInterface::class ),
			Mockery::mock( AdminSettingsGroupInterface::class ),
		);

		$this->assertSame( 2, $registrar->count() );
	}
}
