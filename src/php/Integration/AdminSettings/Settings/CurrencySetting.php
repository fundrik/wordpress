<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Override;

/**
 * Represents the admin setting for the default Fundrik currency.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CurrencySetting implements AdminSettingInterface {

	public const string KEY = 'currency';

	public const string DEFAULT_VALUE = 'RUB';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param AdminSettingsFieldRenderer $field_renderer Renders the setting control.
	 */
	public function __construct(
		private AdminSettingsFieldRenderer $field_renderer,
	) {
	}

	/**
	 * Returns the settings array key.
	 *
	 * @since 1.0.0
	 *
	 * @return string Settings key.
	 */
	#[Override]
	public function get_key(): string {

		return self::KEY;
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

		return __( 'Currency', 'fundrik' );
	}

	/**
	 * Returns the description displayed below the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting description.
	 */
	#[Override]
	public function get_description(): string {

		return __( 'Use a 3-letter ISO 4217 currency code such as RUB or USD.', 'fundrik' );
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

		return self::DEFAULT_VALUE;
	}

	/**
	 * Returns the validation error message for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string Validation error message.
	 */
	#[Override]
	public function get_validation_error_message(): string {

		return __( 'Currency must be a 3-letter ISO 4217 code.', 'fundrik' );
	}

	/**
	 * Normalizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return string|null Normalized setting value, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function normalize_value( mixed $value ): ?string {

		return $this->parse_currency( $value );
	}

	/**
	 * Sanitizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return string|null Sanitized setting value, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function sanitize_value( mixed $value ): ?string {

		return $this->parse_currency( $value );
	}

	/**
	 * Renders the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, bool|float|int|string|null> $args Rendering arguments.
	 *
	 * @phpstan-param array{
	 *     field_name: string,
	 *     input_id: string,
	 *     value: bool|float|int|string|null
	 * } $args Rendering arguments.
	 */
	#[Override]
	public function render( array $args ): void {

		$this->field_renderer->render_text_field(
			$args['field_name'],
			$args['input_id'],
			is_string( $args['value'] ) ? $args['value'] : '',
			3,
		);

		echo '<p class="description">' . esc_html( $this->get_description() ) . '</p>';
	}

	/**
	 * Parses a currency code.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Currency candidate.
	 *
	 * @return string|null Normalized currency code, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function parse_currency( mixed $value ): ?string {

		$currency = is_string( $value ) ? strtoupper( trim( $value ) ) : '';

		if ( preg_match( '/^[A-Z]{3}$/', $currency ) === 1 ) {
			return $currency;
		}

		return null;
	}
}
