<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use InvalidArgumentException;
use Override;

/**
 * Represents the admin setting for the checkout success URL.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CheckoutSuccessUrlSetting implements AdminSettingInterface {

	private const string ID = 'success_url';

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

		return __( 'Success URL', 'fundrik' );
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

		return '';
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
	 * @throws InvalidArgumentException When the value is not a valid URL.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function sanitize_value( mixed $value ): string {

		$url = TypeCaster::to_string( $value );

		if ( filter_var( $url, FILTER_VALIDATE_URL ) !== false ) {
			return $url;
		}

		throw new InvalidArgumentException(
			sprintf( 'Success URL must be a valid URL. Given: %s.', $value ),
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

		SettingFieldRenderer::render_text_field( $args['field_name'], $args['input_id'], $args['value'] );
	}
}
