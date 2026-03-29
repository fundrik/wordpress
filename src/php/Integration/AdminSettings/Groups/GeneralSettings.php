<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsInterface;
use Override;

/**
 * Represents the general settings registered for the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class GeneralSettings implements AdminSettingsInterface {

	public const string OPTION_NAME = 'fundrik_general_settings';

	public const string DEFAULT_CURRENCY_KEY = 'currency';

	public const string DEFAULT_CURRENCY_DEFAULT = 'RUB';

	private const string SECTION_ID = 'fundrik_general_settings';

	private const string DEFAULT_CURRENCY_FIELD_ID = 'fundrik_general_settings_currency';

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers the Fundrik general settings section and fields.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		register_setting(
			AdminSettingsDefinitions::OPTION_GROUP,
			self::OPTION_NAME,
			[
				'type' => 'array',
				'sanitize_callback' => $this->sanitize_settings( ... ),
				'default' => $this->get_default_settings(),
			],
		);

		add_settings_section(
			self::SECTION_ID,
			__( 'General', 'fundrik' ),
			$this->render_general_section_description( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
		);

		add_settings_field(
			self::DEFAULT_CURRENCY_FIELD_ID,
			__( 'Currency', 'fundrik' ),
			$this->render_default_currency_field( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
			self::SECTION_ID,
			[
				'label_for' => self::DEFAULT_CURRENCY_FIELD_ID,
			],
		);
	}
	// phpcs:enable

	/**
	 * Renders the general settings section description.
	 *
	 * @since 1.0.0
	 */
	private function render_general_section_description(): void {

		echo '<p>' . esc_html__( 'Configure global Fundrik settings.', 'fundrik' ) . '</p>';
	}

	/**
	 * Renders the default currency input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Field rendering arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function render_default_currency_field( array $args ): void {

		$input_id = isset( $args['label_for'] ) && is_string( $args['label_for'] )
			? $args['label_for']
			: self::DEFAULT_CURRENCY_FIELD_ID;
		$value = $this->get_current_settings()[ self::DEFAULT_CURRENCY_KEY ];

		printf(
			'<input id="%1$s" name="%2$s" type="text" class="small-text code" maxlength="3" value="%3$s" />',
			esc_attr( $input_id ),
			esc_attr( $this->get_field_name( self::DEFAULT_CURRENCY_KEY ) ),
			esc_attr( $value ),
		);

		echo '<p class="description">' . esc_html__(
			'Use a 3-letter ISO 4217 currency code such as RUB or USD.',
			'fundrik',
		) . '</p>';
	}

	/**
	 * Returns the normalized general settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array{currency: string} General settings values.
	 */
	private function get_current_settings(): array {

		$raw_settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$settings = is_array( $raw_settings ) ? $raw_settings : [];
		$currency = $this->parse_currency( $settings[ self::DEFAULT_CURRENCY_KEY ] ?? null );

		return [
			self::DEFAULT_CURRENCY_KEY => $currency ?? self::DEFAULT_CURRENCY_DEFAULT,
		];
	}

	/**
	 * Sanitizes the submitted general settings values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Submitted option value.
	 *
	 * @return array{currency: string} Sanitized settings values.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_settings( mixed $value ): array {

		$settings = is_array( $value ) ? $value : [];
		$currency = $this->parse_currency( $settings[ self::DEFAULT_CURRENCY_KEY ] ?? null );

		if ( $currency === null ) {
			add_settings_error(
				self::OPTION_NAME,
				self::OPTION_NAME . '_' . self::DEFAULT_CURRENCY_KEY . '_invalid',
				__( 'Currency must be a 3-letter ISO 4217 code.', 'fundrik' ),
			);
		}

		return [
			self::DEFAULT_CURRENCY_KEY => $currency ?? self::DEFAULT_CURRENCY_DEFAULT,
		];
	}

	/**
	 * Returns the field name for a settings array key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $setting_key Settings array key.
	 *
	 * @return string HTML form field name.
	 */
	private function get_field_name( string $setting_key ): string {

		return sprintf( '%s[%s]', self::OPTION_NAME, $setting_key );
	}

	/**
	 * Returns the default general settings values.
	 *
	 * @since 1.0.0
	 *
	 * @return array{currency: string} Default settings values.
	 */
	private function get_default_settings(): array {

		return [
			self::DEFAULT_CURRENCY_KEY => self::DEFAULT_CURRENCY_DEFAULT,
		];
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
