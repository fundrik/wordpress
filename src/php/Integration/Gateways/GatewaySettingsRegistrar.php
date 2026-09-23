<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingCheckboxField;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingFieldInterface;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\WpSchemaType;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

/**
 * Registers gateway settings fields.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class GatewaySettingsRegistrar {

	private const string ENABLED_FIELD_ID = 'enabled';

	/**
	 * Gateway instances.
	 *
	 * @var list<GatewayInterface>
	 */
	private array $gateways;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param OptionReader $option_reader Provides typed helpers for WordPress options.
	 * @param GatewayInterface ...$gateways Gateway instances.
	 */
	public function __construct(
		private OptionReader $option_reader,
		GatewayInterface ...$gateways,
	) {

		$this->gateways = $gateways;
	}

	/**
	 * Registers settings declared by all gateways.
	 *
	 * @since 1.0.0
	 */
	public function register_all(): void {

		foreach ( $this->gateways as $gateway ) {

			$this->register_section( $gateway );

			foreach ( $this->get_fields( $gateway ) as $field ) {

				$this->register_field( $gateway, $field );
			}
		}
	}

	/**
	 * Returns the common and gateway-specific settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @param GatewayInterface $gateway Gateway definition.
	 *
	 * @return list<GatewaySettingFieldInterface> Gateway settings fields.
	 *
	 * @throws LogicException When a gateway overrides the reserved enabled field.
	 */
	private function get_fields( GatewayInterface $gateway ): array {

		$gateway_fields = $gateway->get_settings_fields();

		foreach ( $gateway_fields as $field ) {

			if ( $field->get_id() === self::ENABLED_FIELD_ID ) {
				throw new LogicException( 'Gateway settings cannot define the reserved "enabled" field.' );
			}
		}

		return [
			$this->create_enabled_field( $gateway ),
			...$gateway_fields,
		];
	}

	/**
	 * Creates the common enabled field for a gateway.
	 *
	 * @since 1.0.0
	 *
	 * @param GatewayInterface $gateway Gateway definition.
	 *
	 * @return GatewaySettingFieldInterface Enabled field.
	 */
	private function create_enabled_field( GatewayInterface $gateway ): GatewaySettingFieldInterface {

		return new GatewaySettingCheckboxField(
			id: self::ENABLED_FIELD_ID,
			label: sprintf(
				/* translators: %s: Gateway label. */
				__( 'Enable %s', 'fundrik' ),
				$gateway->get_label(),
			),
			default_value: false,
		);
	}

	/**
	 * Registers a gateway settings section.
	 *
	 * @since 1.0.0
	 *
	 * @param GatewayInterface $gateway Gateway definition.
	 */
	private function register_section( GatewayInterface $gateway ): void {

		add_settings_section(
			$this->get_section_id( $gateway ),
			$gateway->get_label(),
			$gateway->render_settings_description( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
		);
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers gateway settings field.
	 *
	 * @since 1.0.0
	 *
	 * @param GatewayInterface $gateway Gateway definition.
	 * @param GatewaySettingFieldInterface $field Gateway field definition.
	 */
	private function register_field( GatewayInterface $gateway, GatewaySettingFieldInterface $field ): void {

		$option_name = SettingOptionName::create( $gateway->get_id(), $field->get_id() );
		$current_value = $this->get_current_value( $option_name, $field );

		add_settings_field(
			$option_name,
			$field->get_label(),
			$field->render( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
			$this->get_section_id( $gateway ),
			[
				'field_name' => $option_name,
				'input_id' => $option_name,
				'value' => $current_value,
				'label_for' => $option_name,
			],
		);

		register_setting(
			AdminPageDefinitions::SETTINGS_PAGE_ID,
			$option_name,
			[
				'type' => $field->get_value_type()->value,
				'sanitize_callback' => fn ( mixed $value ): bool|int|string => $this->sanitize_field_value(
					$option_name,
					$field,
					$current_value,
					$value,
				),
				'default' => $field->get_default_value(),
			],
		);
	}
	// phpcs:enable

	/**
	 * Returns the current gateway field value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name WordPress option name.
	 * @param GatewaySettingFieldInterface $field Gateway field definition.
	 *
	 * @return int|string|bool Current field value.
	 */
	private function get_current_value( string $option_name, GatewaySettingFieldInterface $field ): int|string|bool {

		try {
			return match ( $field->get_value_type() ) {
				WpSchemaType::Boolean => $this->option_reader->find_bool_option( $option_name ),
				WpSchemaType::Integer => $this->option_reader->find_int_option( $option_name ),
				WpSchemaType::String => $this->option_reader->find_string_option( $option_name ),
			} ?? $field->get_default_value();
		} catch ( UnexpectedValueException ) {
			return $field->get_default_value();
		}
	}

	/**
	 * Sanitizes a gateway field value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name WordPress option name.
	 * @param GatewaySettingFieldInterface $field Gateway field definition.
	 * @param int|string|bool $current_value Current usable value.
	 * @param mixed $value Raw field value.
	 *
	 * @return int|string|bool Sanitized field value.
	 *
	 * @throws InvalidArgumentException When the value cannot be converted to the field type.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_field_value(
		string $option_name,
		GatewaySettingFieldInterface $field,
		int|string|bool $current_value,
		mixed $value,
	): int|string|bool {

		try {
			return match ( $field->get_value_type() ) {
				WpSchemaType::Boolean => TypeCaster::to_bool( $value ),
				WpSchemaType::Integer => TypeCaster::to_int( $value ),
				WpSchemaType::String => TypeCaster::to_string( $value ),
			};
		} catch ( InvalidArgumentException $exception ) {

			add_settings_error(
				$option_name,
				$option_name . '_invalid',
				$exception->getMessage(),
			);

			return $current_value;
		}
	}

	/**
	 * Returns the settings section ID for a gateway.
	 *
	 * @since 1.0.0
	 *
	 * @param GatewayInterface $gateway Gateway definition.
	 *
	 * @return string Settings section ID.
	 */
	private function get_section_id( GatewayInterface $gateway ): string {

		return 'gateway_' . $gateway->get_id();
	}
}
