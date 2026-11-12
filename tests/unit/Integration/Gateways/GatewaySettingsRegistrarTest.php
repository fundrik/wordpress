<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\Gateways\GatewaySettingsRegistrar;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingCheckboxField;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingFieldInterface;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\Fixtures\FakeWordPressSettingsApi;
use Fundrik\WordPress\Tests\WordPressTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( GatewaySettingsRegistrar::class )]
#[UsesClass( AdminPageDefinitions::class )]
#[UsesClass( GatewaySettingCheckboxField::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SettingOptionName::class )]
final class GatewaySettingsRegistrarTest extends WordPressTestCase {

	private FakeWordPressSettingsApi $settings_api;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->settings_api = new FakeWordPressSettingsApi();
		$this->settings_api->stub();
	}

	#[Test]
	public function register_all_registers_gateway_section_enabled_field_and_declared_field(): void {

		$field = $this->make_field( 'api_key', 'API key', WpSchemaType::String, 'default-key' );
		$gateway = $this->make_gateway( 'example', 'Example Gateway', $field );
		$registrar = $this->make_registrar(
			new FakeStorage(
				[
					'fundrik_example_api_key_setting' => 'configured-key',
				],
			),
			$gateway,
		);

		$gateway->shouldReceive( 'render_settings_description' )->once();
		$field
			->shouldReceive( 'render' )
			->once()
			->with(
				[
					'field_name' => 'fundrik_example_api_key_setting',
					'input_id' => 'fundrik_example_api_key_setting',
					'value' => 'configured-key',
					'label_for' => 'fundrik_example_api_key_setting',
				],
			);

		$registrar->register_all();

		$section = $this->settings_api->get_section( 'gateway_example' );
		self::assertSame(
			[
				'title' => 'Example Gateway',
				'callback' => [ $gateway, 'render_settings_description' ],
				'page' => AdminPageDefinitions::ROOT_MENU_SLUG,
			],
			$section,
		);

		$enabled_field = $this->settings_api->get_field( 'fundrik_example_enabled_setting' );
		self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
			[
				'title' => 'Enable Example Gateway',
				'page' => AdminPageDefinitions::ROOT_MENU_SLUG,
				'section' => 'gateway_example',
				'args' => [
					'field_name' => 'fundrik_example_enabled_setting',
					'input_id' => 'fundrik_example_enabled_setting',
					'value' => false,
					'label_for' => 'fundrik_example_enabled_setting',
				],
			],
			$enabled_field,
			[ 'callback' ],
		);
		self::assertIsArray( $enabled_field['callback'] );
		self::assertInstanceOf( GatewaySettingCheckboxField::class, $enabled_field['callback'][0] );
		self::assertSame( 'render', $enabled_field['callback'][1] );

		$declared_field = $this->settings_api->get_field( 'fundrik_example_api_key_setting' );
		self::assertSame(
			[
				'title' => 'API key',
				'callback' => [ $field, 'render' ],
				'page' => AdminPageDefinitions::ROOT_MENU_SLUG,
				'section' => 'gateway_example',
				'args' => [
					'field_name' => 'fundrik_example_api_key_setting',
					'input_id' => 'fundrik_example_api_key_setting',
					'value' => 'configured-key',
					'label_for' => 'fundrik_example_api_key_setting',
				],
			],
			$declared_field,
		);

		$enabled_setting = $this->settings_api->get_setting( 'fundrik_example_enabled_setting' );
		self::assertSame( AdminPageDefinitions::SETTINGS_PAGE_ID, $enabled_setting['group'] );
		self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
			[
				'type' => WpSchemaType::Boolean->value,
				'default' => false,
			],
			$enabled_setting['args'],
			[ 'sanitize_callback' ],
		);
		self::assertIsCallable( $enabled_setting['args']['sanitize_callback'] );

		$declared_setting = $this->settings_api->get_setting( 'fundrik_example_api_key_setting' );
		self::assertSame( AdminPageDefinitions::SETTINGS_PAGE_ID, $declared_setting['group'] );
		self::assertArrayIsIdenticalToArrayIgnoringListOfKeys(
			[
				'type' => WpSchemaType::String->value,
				'default' => 'default-key',
			],
			$declared_setting['args'],
			[ 'sanitize_callback' ],
		);
		self::assertIsCallable( $declared_setting['args']['sanitize_callback'] );

		$section['callback']();
		$declared_field['callback']( $declared_field['args'] );
	}

	#[Test]
	#[DataProvider( 'supported_field_type_cases' )]
	public function register_all_reads_and_sanitizes_supported_field_type(
		WpSchemaType $type,
		bool|int|string $default_value,
		mixed $stored_value,
		bool|int|string $expected_current_value,
		mixed $new_value,
		bool|int|string $expected_sanitized_value,
	): void {

		$option_name = 'fundrik_example_custom_setting';
		$field = $this->make_field( 'custom', 'Custom field', $type, $default_value );

		$this->make_registrar(
			new FakeStorage( [ $option_name => $stored_value ] ),
			$this->make_gateway( 'example', 'Example Gateway', $field ),
		)->register_all();

		$field_registration = $this->settings_api->get_field( $option_name );
		self::assertSame( $expected_current_value, $field_registration['args']['value'] );

		$setting_registration = $this->settings_api->get_setting( $option_name );
		self::assertSame( $type->value, $setting_registration['args']['type'] );
		self::assertSame( $expected_sanitized_value, $setting_registration['args']['sanitize_callback']( $new_value ) );
	}

	#[Test]
	#[DataProvider( 'unusable_current_value_cases' )]
	public function register_all_uses_default_when_current_value_is_unusable( array $stored_values ): void {

		$option_name = 'fundrik_example_api_key_setting';
		$field = $this->make_field( 'api_key', 'API key', WpSchemaType::String, 'default-key' );

		$this->make_registrar(
			new FakeStorage( $stored_values ),
			$this->make_gateway( 'example', 'Example Gateway', $field ),
		)->register_all();

		$field_registration = $this->settings_api->get_field( $option_name );

		self::assertSame( 'default-key', $field_registration['args']['value'] );
	}

	#[Test]
	public function register_all_preserves_current_value_when_sanitization_fails(): void {

		$option_name = 'fundrik_example_timeout_setting';
		$field = $this->make_field( 'timeout', 'Timeout', WpSchemaType::Integer, 10 );

		$this->make_registrar(
			new FakeStorage( [ $option_name => '25' ] ),
			$this->make_gateway( 'example', 'Example Gateway', $field ),
		)->register_all();

		$setting_registration = $this->settings_api->get_setting( $option_name );

		self::assertSame( 25, $setting_registration['args']['sanitize_callback']( false ) );
		self::assertCount( 1, $this->settings_api->get_errors() );
		self::assertSame(
			[
				'setting' => $option_name,
				'code' => $option_name . '_invalid',
				'message' => 'Value must be int. Given: bool.',
				'type' => 'error',
			],
			$this->settings_api->get_errors()[0],
		);
	}

	#[Test]
	public function register_all_throws_when_gateway_declares_reserved_enabled_field(): void {

		$field = $this->make_field( 'enabled', 'Custom enabled', WpSchemaType::Boolean, true );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Gateway settings cannot define the reserved "enabled" field.' );

		$this->make_registrar(
			new FakeStorage(),
			$this->make_gateway( 'example', 'Example Gateway', $field ),
		)->register_all();
	}

	#[Test]
	#[DataProvider( 'unsupported_schema_type_cases' )]
	public function register_all_throws_for_unsupported_schema_type( WpSchemaType $type ): void {

		$field = $this->make_field( 'unsupported', 'Unsupported', $type, '' );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Gateway settings do not support the configured schema type.' );

		$this->make_registrar(
			new FakeStorage(),
			$this->make_gateway( 'example', 'Example Gateway', $field ),
		)->register_all();
	}

	/**
	 * Returns supported gateway field type cases.
	 *
	 * @return iterable<string, array{WpSchemaType, bool|int|string, mixed, bool|int|string, mixed, bool|int|string}> Field type cases.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public static function supported_field_type_cases(): iterable {

		yield 'boolean' => [ WpSchemaType::Boolean, false, '0', false, '1', true ];
		yield 'integer' => [ WpSchemaType::Integer, 10, '25', 25, '30', 30 ];
		yield 'string' => [ WpSchemaType::String, 'default', 'configured', 'configured', 'new', 'new' ];
	}

	/**
	 * Returns unusable current value cases.
	 *
	 * @return iterable<string, array{array<string, mixed>}> Stored option cases.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public static function unusable_current_value_cases(): iterable {

		yield 'option missing' => [ [] ];
		yield 'option has invalid type' => [ [ 'fundrik_example_api_key_setting' => false ] ];
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

	private function make_field(
		string $id,
		string $label,
		WpSchemaType $type,
		bool|int|string $default_value,
	): GatewaySettingFieldInterface&MockInterface {

		$field = Mockery::mock( GatewaySettingFieldInterface::class );
		$field->shouldReceive( 'get_id' )->andReturn( $id );
		$field->shouldReceive( 'get_label' )->andReturn( $label );
		$field->shouldReceive( 'get_value_type' )->andReturn( $type );
		$field->shouldReceive( 'get_default_value' )->andReturn( $default_value );

		return $field;
	}

	private function make_gateway(
		string $id,
		string $label,
		GatewaySettingFieldInterface ...$fields,
	): GatewayInterface&MockInterface {

		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway->shouldReceive( 'get_id' )->andReturn( $id );
		$gateway->shouldReceive( 'get_label' )->andReturn( $label );
		$gateway->shouldReceive( 'get_settings_fields' )->andReturn( $fields );

		return $gateway;
	}

	private function make_registrar( FakeStorage $storage, GatewayInterface ...$gateways ): GatewaySettingsRegistrar {

		return new GatewaySettingsRegistrar( new OptionReader( $storage ), ...$gateways );
	}
}
