<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use InvalidArgumentException;
use Override;

/**
 * Represents the admin setting for the default Fundrik currency.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CurrencySetting implements AdminSettingInterface {

	private const string ID = 'currency';

	private const string DEFAULT_VALUE = 'RUB';

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

		return __( 'Currency', 'fundrik' );
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
	 * Normalizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return string Normalized setting value.
	 *
	 * @throws InvalidArgumentException When the value is not a valid currency code.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function normalize_value( mixed $value ): string {

		return $this->parse_currency( $value );
	}

	/**
	 * Sanitizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return string Sanitized setting value.
	 *
	 * @throws InvalidArgumentException When the value is not a valid currency code.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function sanitize_value( mixed $value ): string {

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
	 * } $args
	 */
	#[Override]
	public function render( array $args ): void {

		$this->field_renderer->render_text_field(
			$args['field_name'],
			$args['input_id'],
			$args['value'],
			maxlength: 3,
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Use a 3-letter ISO 4217 currency code such as RUB or USD.', 'fundrik' ),
		);
	}

	/**
	 * Parses a currency code.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Currency candidate.
	 *
	 * @return string Normalized currency code.
	 *
	 * @throws InvalidArgumentException When the value is not a valid currency code.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function parse_currency( mixed $value ): string {

		$currency = strtoupper( trim( TypeCaster::to_string( $value ) ) );

		if ( preg_match( '/^[A-Z]{3}$/', $currency ) === 1 ) {
			return $currency;
		}

		throw new InvalidArgumentException(
			sprintf( 'Currency must be a 3-letter ISO 4217 code. Given: %s.', $value ),
		);
	}
}
