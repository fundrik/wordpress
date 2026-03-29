<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Groups;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsDefinitions;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsInterface;
use Override;

/**
 * Represents the donation form defaults registered for the Fundrik plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormSettings implements AdminSettingsInterface {

	public const string OPTION_NAME = 'fundrik_donation_form_settings';

	public const string DEFAULT_DONATION_AMOUNT_KEY = 'default_amount';

	public const int DEFAULT_DONATION_AMOUNT_DEFAULT = 10;

	public const string DEFAULT_AMOUNT_LABEL_KEY = 'default_amount_label';

	public const string DEFAULT_AMOUNT_LABEL_DEFAULT = 'Amount';

	private const string SECTION_ID = 'fundrik_donation_form_settings';

	private const string DEFAULT_DONATION_AMOUNT_FIELD_ID = 'fundrik_donation_form_settings_default_amount';

	private const string DEFAULT_AMOUNT_LABEL_FIELD_ID = 'fundrik_donation_form_settings_default_amount_label';

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers the donation form defaults section and fields.
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
			__( 'Donation Form', 'fundrik' ),
			$this->render_section_description( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
		);

		add_settings_field(
			self::DEFAULT_DONATION_AMOUNT_FIELD_ID,
			__( 'Default donation amount', 'fundrik' ),
			$this->render_default_donation_amount_field( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
			self::SECTION_ID,
			[
				'label_for' => self::DEFAULT_DONATION_AMOUNT_FIELD_ID,
			],
		);

		add_settings_field(
			self::DEFAULT_AMOUNT_LABEL_FIELD_ID,
			__( 'Default amount label', 'fundrik' ),
			$this->render_default_amount_label_field( ... ),
			AdminPageDefinitions::ROOT_MENU_SLUG,
			self::SECTION_ID,
			[
				'label_for' => self::DEFAULT_AMOUNT_LABEL_FIELD_ID,
			],
		);
	}
	// phpcs:enable

	/**
	 * Renders the donation form section description.
	 *
	 * @since 1.0.0
	 */
	private function render_section_description(): void {

		echo '<p>' . esc_html__(
			'Configure the defaults used by donation form blocks when a block does not override them.',
			'fundrik',
		) . '</p>';
	}

	/**
	 * Renders the default donation amount input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Field rendering arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function render_default_donation_amount_field( array $args ): void {

		$input_id = isset( $args['label_for'] ) && is_string( $args['label_for'] )
			? $args['label_for']
			: self::DEFAULT_DONATION_AMOUNT_FIELD_ID;
		$value = $this->get_current_settings()[ self::DEFAULT_DONATION_AMOUNT_KEY ];

		printf(
			'<input id="%1$s" name="%2$s" type="number" class="small-text" min="1" step="1" value="%3$s" />',
			esc_attr( $input_id ),
			esc_attr( $this->get_field_name( self::DEFAULT_DONATION_AMOUNT_KEY ) ),
			esc_attr( (string) $value ),
		);

		echo '<p class="description">' . esc_html__(
			'Used when the donation form block does not define its own default amount.',
			'fundrik',
		) . '</p>';
	}

	/**
	 * Renders the default amount label input field.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Field rendering arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function render_default_amount_label_field( array $args ): void {

		$input_id = isset( $args['label_for'] ) && is_string( $args['label_for'] )
			? $args['label_for']
			: self::DEFAULT_AMOUNT_LABEL_FIELD_ID;
		$value = $this->get_current_settings()[ self::DEFAULT_AMOUNT_LABEL_KEY ];

		printf(
			'<input id="%1$s" name="%2$s" type="text" class="regular-text" value="%3$s" />',
			esc_attr( $input_id ),
			esc_attr( $this->get_field_name( self::DEFAULT_AMOUNT_LABEL_KEY ) ),
			esc_attr( $value ),
		);

		echo '<p class="description">' . esc_html__(
			'Used when the donation form block does not define its own amount label.',
			'fundrik',
		) . '</p>';
	}

	/**
	 * Returns the normalized donation form settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array{default_amount: int, default_amount_label: string} Donation form settings values.
	 */
	private function get_current_settings(): array {

		$raw_settings = get_option( self::OPTION_NAME, $this->get_default_settings() );
		$settings = is_array( $raw_settings ) ? $raw_settings : [];
		$default_amount = $this->parse_default_donation_amount(
			$settings[ self::DEFAULT_DONATION_AMOUNT_KEY ] ?? null,
		);
		$default_amount_label = $this->parse_default_amount_label(
			$settings[ self::DEFAULT_AMOUNT_LABEL_KEY ] ?? null,
		);

		return [
			self::DEFAULT_DONATION_AMOUNT_KEY => $default_amount ?? self::DEFAULT_DONATION_AMOUNT_DEFAULT,
			self::DEFAULT_AMOUNT_LABEL_KEY => $default_amount_label ?? self::DEFAULT_AMOUNT_LABEL_DEFAULT,
		];
	}

	/**
	 * Sanitizes the submitted donation form settings values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Submitted option value.
	 *
	 * @return array{default_amount: int, default_amount_label: string} Sanitized settings values.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function sanitize_settings( mixed $value ): array {

		$settings = is_array( $value ) ? $value : [];
		$default_amount = $this->parse_default_donation_amount(
			$settings[ self::DEFAULT_DONATION_AMOUNT_KEY ] ?? null,
		);
		$default_amount_label = $this->parse_default_amount_label(
			$settings[ self::DEFAULT_AMOUNT_LABEL_KEY ] ?? null,
		);

		if ( $default_amount === null ) {
			$this->add_invalid_default_amount_error();
		}

		if ( $default_amount_label === null ) {
			$this->add_invalid_default_amount_label_error();
		}

		return [
			self::DEFAULT_DONATION_AMOUNT_KEY => $default_amount ?? self::DEFAULT_DONATION_AMOUNT_DEFAULT,
			self::DEFAULT_AMOUNT_LABEL_KEY => $default_amount_label ?? self::DEFAULT_AMOUNT_LABEL_DEFAULT,
		];
	}

	/**
	 * Registers the validation error for an invalid default donation amount.
	 *
	 * @since 1.0.0
	 */
	private function add_invalid_default_amount_error(): void {

		add_settings_error(
			self::OPTION_NAME,
			self::OPTION_NAME . '_' . self::DEFAULT_DONATION_AMOUNT_KEY . '_invalid',
			__( 'Default donation amount must be a positive integer.', 'fundrik' ),
		);
	}

	/**
	 * Registers the validation error for an invalid default amount label.
	 *
	 * @since 1.0.0
	 */
	private function add_invalid_default_amount_label_error(): void {

		add_settings_error(
			self::OPTION_NAME,
			self::OPTION_NAME . '_' . self::DEFAULT_AMOUNT_LABEL_KEY . '_invalid',
			__( 'Default amount label must not be empty.', 'fundrik' ),
		);
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
	 * Returns the default donation form settings values.
	 *
	 * @since 1.0.0
	 *
	 * @return array{default_amount: int, default_amount_label: string} Default settings values.
	 */
	private function get_default_settings(): array {

		return [
			self::DEFAULT_DONATION_AMOUNT_KEY => self::DEFAULT_DONATION_AMOUNT_DEFAULT,
			self::DEFAULT_AMOUNT_LABEL_KEY => self::DEFAULT_AMOUNT_LABEL_DEFAULT,
		];
	}

	/**
	 * Parses a positive integer default donation amount.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Amount candidate.
	 *
	 * @return int|null Positive integer amount, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function parse_default_donation_amount( mixed $value ): ?int {

		$default_amount = filter_var(
			$value,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 1,
				],
			],
		);

		if ( $default_amount !== false ) {
			return $default_amount;
		}

		return null;
	}

	/**
	 * Parses a non-empty default amount label.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Amount label candidate.
	 *
	 * @return string|null Amount label, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function parse_default_amount_label( mixed $value ): ?string {

		$default_amount_label = is_string( $value ) ? trim( $value ) : '';

		if ( $default_amount_label !== '' ) {
			return $default_amount_label;
		}

		return null;
	}
}
