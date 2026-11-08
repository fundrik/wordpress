<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingRegistration;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\Fixtures\FakeStorageNotFoundException;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( AdminSettingsGroupRegistrar::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( AdminSettingRegistration::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SettingOptionName::class )]
final class AdminSettingsGroupRegistrarTest extends WordPressTestCase {

	private StoragePort&MockInterface $storage;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->storage = Mockery::mock( StoragePort::class );
	}

	#[Test]
	public function register_all_registers_section_field_and_string_option(): void {

		$setting = $this->make_setting( 'currency', 'Currency', WpSchemaType::String, 'RUB' );
		$group = $this->make_group( 'general', 'General', $setting );
		$option_name = 'fundrik_general_currency_setting';
		$field_args = [
			'field_name' => $option_name,
			'input_id' => $option_name,
			'value' => 'USD',
			'label_for' => $option_name,
		];
		$section_callback = null;
		$render_callback = null;
		$registration_args = null;

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( $option_name )
			->andReturn( 'USD' );

		$group->shouldReceive( 'render_section_description' )->once();
		$setting->shouldReceive( 'render' )->once()->with( $field_args );
		$setting->shouldReceive( 'sanitize_value' )->once()->with( ' eur ' )->andReturn( 'EUR' );

		Functions\expect( 'add_settings_section' )
			->once()
			->with(
				'general',
				'General',
				Mockery::capture( $section_callback ),
				AdminPageDefinitions::ROOT_MENU_SLUG,
			);

		Functions\expect( 'add_settings_field' )
			->once()
			->with(
				$option_name,
				'Currency',
				Mockery::capture( $render_callback ),
				AdminPageDefinitions::ROOT_MENU_SLUG,
				'general',
				$field_args,
			);

		Functions\expect( 'register_setting' )
			->once()
			->with(
				AdminPageDefinitions::SETTINGS_PAGE_ID,
				$option_name,
				Mockery::capture( $registration_args ),
			);

		$this->make_registrar( $group )->register_all();

		self::assertIsCallable( $section_callback );
		self::assertIsCallable( $render_callback );
		self::assertIsArray( $registration_args );
		self::assertSame( WpSchemaType::String->value, $registration_args['type'] );
		self::assertSame( 'RUB', $registration_args['default'] );
		self::assertIsCallable( $registration_args['sanitize_callback'] );

		$section_callback();
		$render_callback( $field_args );
		self::assertSame( 'EUR', $registration_args['sanitize_callback']( ' eur ' ) );
	}

	#[Test]
	#[DataProvider( 'typed_current_value_cases' )]
	public function register_all_uses_typed_current_value(
		WpSchemaType $type,
		bool|int $default_value,
		string $stored_value,
		bool|int $expected_value,
	): void {

		$setting = $this->make_setting( 'typed_setting', 'Typed setting', $type, $default_value );
		$group = $this->make_group( 'test_group', 'Test group', $setting );
		$option_name = 'fundrik_test_group_typed_setting_setting';
		$field_args = null;
		$registration_args = null;

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( $option_name )
			->andReturn( $stored_value );

		Functions\expect( 'add_settings_section' )->once();
		Functions\expect( 'add_settings_field' )
			->once()
			->with(
				$option_name,
				'Typed setting',
				Mockery::type( 'callable' ),
				AdminPageDefinitions::ROOT_MENU_SLUG,
				'test_group',
				Mockery::capture( $field_args ),
			);
		Functions\expect( 'register_setting' )
			->once()
			->with(
				AdminPageDefinitions::SETTINGS_PAGE_ID,
				$option_name,
				Mockery::capture( $registration_args ),
			);

		$this->make_registrar( $group )->register_all();

		self::assertIsArray( $field_args );
		self::assertSame( $expected_value, $field_args['value'] );
		self::assertIsArray( $registration_args );
		self::assertSame( $type->value, $registration_args['type'] );
	}

	#[Test]
	public function register_all_uses_default_when_option_is_missing(): void {

		$setting = $this->make_setting( 'currency', 'Currency', WpSchemaType::String, 'RUB' );
		$group = $this->make_group( 'general', 'General', $setting );
		$option_name = 'fundrik_general_currency_setting';
		$field_args = null;

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( $option_name )
			->andThrow( new FakeStorageNotFoundException( 'Missing.' ) );

		Functions\expect( 'add_settings_section' )->once();
		Functions\expect( 'add_settings_field' )
			->once()
			->with(
				$option_name,
				'Currency',
				Mockery::type( 'callable' ),
				AdminPageDefinitions::ROOT_MENU_SLUG,
				'general',
				Mockery::capture( $field_args ),
			);
		Functions\expect( 'register_setting' )->once();

		$this->make_registrar( $group )->register_all();

		self::assertIsArray( $field_args );
		self::assertSame( 'RUB', $field_args['value'] );
	}

	#[Test]
	public function register_all_uses_default_when_stored_option_has_invalid_type(): void {

		$setting = $this->make_setting( 'currency', 'Currency', WpSchemaType::String, 'RUB' );
		$group = $this->make_group( 'general', 'General', $setting );
		$option_name = 'fundrik_general_currency_setting';
		$field_args = null;

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( $option_name )
			->andReturn( false );

		Functions\expect( 'add_settings_section' )->once();
		Functions\expect( 'add_settings_field' )
			->once()
			->with(
				$option_name,
				'Currency',
				Mockery::type( 'callable' ),
				AdminPageDefinitions::ROOT_MENU_SLUG,
				'general',
				Mockery::capture( $field_args ),
			);
		Functions\expect( 'register_setting' )->once();

		$this->make_registrar( $group )->register_all();

		self::assertIsArray( $field_args );
		self::assertSame( 'RUB', $field_args['value'] );
	}

	#[Test]
	#[DataProvider( 'unsupported_schema_type_cases' )]
	public function register_all_throws_for_unsupported_schema_type( WpSchemaType $type ): void {

		$setting = $this->make_setting( 'unsupported', 'Unsupported', $type, '' );
		$group = $this->make_group( 'test_group', 'Test group', $setting );

		Functions\expect( 'add_settings_section' )->once();

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Admin settings do not support the configured schema type.' );

		$this->make_registrar( $group )->register_all();
	}

	#[Test]
	public function register_all_preserves_current_value_when_sanitization_fails(): void {

		$setting = $this->make_setting( 'currency', 'Currency', WpSchemaType::String, 'RUB' );
		$group = $this->make_group( 'general', 'General', $setting );
		$option_name = 'fundrik_general_currency_setting';
		$registration_args = null;
		$exception_message = 'Currency must be a 3-letter ISO 4217 code. Given: BOOL.';

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( $option_name )
			->andReturn( 'USD' );

		$setting
			->shouldReceive( 'sanitize_value' )
			->once()
			->with( false )
			->andThrow( new InvalidArgumentException( $exception_message ) );

		Functions\expect( 'add_settings_section' )->once();
		Functions\expect( 'add_settings_field' )->once();
		Functions\expect( 'register_setting' )
			->once()
			->with(
				AdminPageDefinitions::SETTINGS_PAGE_ID,
				$option_name,
				Mockery::capture( $registration_args ),
			);
		Functions\expect( 'add_settings_error' )
			->once()
			->with( $option_name, $option_name . '_invalid', $exception_message );

		$this->make_registrar( $group )->register_all();

		self::assertIsArray( $registration_args );
		self::assertIsCallable( $registration_args['sanitize_callback'] );
		self::assertSame( 'USD', $registration_args['sanitize_callback']( false ) );
	}

	#[Test]
	public function count_returns_number_of_configured_groups(): void {

		$first_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$second_group = Mockery::mock( AdminSettingsGroupInterface::class );

		self::assertSame( 2, $this->make_registrar( $first_group, $second_group )->count() );
	}

	/**
	 * Returns supported typed current value cases.
	 *
	 * @return iterable<string, array{WpSchemaType, bool|int, string, bool|int}> Typed current value cases.
	 */
	public static function typed_current_value_cases(): iterable {

		yield 'boolean' => [ WpSchemaType::Boolean, true, '0', false ];
		yield 'integer' => [ WpSchemaType::Integer, 10, '25', 25 ];
	}

	/**
	 * Returns unsupported schema type cases.
	 *
	 * @return iterable<string, array{WpSchemaType}> Unsupported schema type cases.
	 */
	public static function unsupported_schema_type_cases(): iterable {

		yield 'number' => [ WpSchemaType::Number ];
		yield 'array' => [ WpSchemaType::Array ];
		yield 'object' => [ WpSchemaType::Object ];
	}

	private function make_group(
		string $id,
		string $title,
		AdminSettingInterface $setting,
	): AdminSettingsGroupInterface&MockInterface {

		$group = Mockery::mock( AdminSettingsGroupInterface::class );
		$group->shouldReceive( 'get_id' )->andReturn( $id );
		$group->shouldReceive( 'get_section_title' )->andReturn( $title );
		$group->shouldReceive( 'get_settings' )->andReturn( [ $setting ] );

		return $group;
	}

	private function make_registrar( AdminSettingsGroupInterface ...$groups ): AdminSettingsGroupRegistrar {

		return new AdminSettingsGroupRegistrar( new OptionReader( $this->storage ), ...$groups );
	}

	private function make_setting(
		string $id,
		string $label,
		WpSchemaType $type,
		bool|int|string $default_value,
	): AdminSettingInterface&MockInterface {

		$setting = Mockery::mock( AdminSettingInterface::class );
		$setting->shouldReceive( 'get_id' )->andReturn( $id );
		$setting->shouldReceive( 'get_label' )->andReturn( $label );
		$setting->shouldReceive( 'get_value_type' )->andReturn( $type );
		$setting->shouldReceive( 'get_default_value' )->andReturn( $default_value );

		return $setting;
	}
}
