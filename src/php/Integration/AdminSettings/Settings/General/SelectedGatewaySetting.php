<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings\General;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\WpSchemaType;
use InvalidArgumentException;
use Override;

/**
 * Represents the admin setting for the active gateway.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class SelectedGatewaySetting implements AdminSettingInterface {

	private const string ID = 'selected_gateway';

	/**
	 * Configured gateway instances.
	 *
	 * @var list<GatewayInterface>
	 */
	private array $gateways;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsFieldRenderer $field_renderer Renders the setting control.
	 * @param GatewayInterface ...$gateways Configured gateway instances.
	 */
	public function __construct(
		private AdminSettingsFieldRenderer $field_renderer,
		GatewayInterface ...$gateways,
	) {

		$this->gateways = $gateways;
	}

	/**
	 * Returns the setting ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting ID.
	 */
	#[Override]
	public function get_id(): string {

		return self::ID;
	}

	/**
	 * Returns the label displayed for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting label.
	 */
	#[Override]
	public function get_label(): string {

		return __( 'Selected gateway', 'fundrik' );
	}

	/**
	 * Returns the default value for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string Default setting value.
	 */
	#[Override]
	public function get_default_value(): string {

		if ( $this->gateways === [] ) {
			return '';
		}

		return $this->gateways[0]->get_id();
	}

	/**
	 * Returns the expected value type for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return WpSchemaType Setting value type.
	 */
	#[Override]
	public function get_value_type(): WpSchemaType {

		return WpSchemaType::String;
	}

	/**
	 * Sanitizes the setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return string Sanitized setting value.
	 *
	 * @throws InvalidArgumentException When the value is not one of the configured gateway IDs.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function sanitize_value( mixed $value ): string {

		$gateway_id = trim( TypeCaster::to_string( $value ) );

		if ( $gateway_id === '' ) {
			return $this->get_default_value();
		}

		foreach ( $this->gateways as $gateway ) {

			if ( $gateway->get_id() === $gateway_id ) {
				return $gateway_id;
			}
		}

		throw new InvalidArgumentException(
			sprintf(
				'Active gateway must be one of: %s. Given: %s.',
				implode( ', ', $this->get_gateway_ids() ),
				$gateway_id,
			),
		);
	}

	/**
	 * Renders the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int|string> $args Rendering arguments.
	 *
	 * @phpstan-param array{
	 *     field_name: string,
	 *     input_id: string,
	 *     value: string
	 * } $args
	 */
	#[Override]
	public function render( array $args ): void {

		$this->field_renderer->render_select_field(
			$args['field_name'],
			$args['input_id'],
			$args['value'],
			$this->get_gateway_options(),
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Select the gateway used for donation checkout.', 'fundrik' ),
		);
	}

	/**
	 * Returns the configured gateway options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Gateway options keyed by gateway ID.
	 */
	private function get_gateway_options(): array {

		$options = [];

		foreach ( $this->gateways as $gateway ) {
			$options[ $gateway->get_id() ] = $gateway->get_label();
		}

		return $options;
	}

	/**
	 * Returns the configured gateway IDs.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Gateway IDs.
	 */
	private function get_gateway_ids(): array {

		return array_map(
			static fn ( GatewayInterface $gateway ): string => $gateway->get_id(),
			$this->gateways,
		);
	}
}
