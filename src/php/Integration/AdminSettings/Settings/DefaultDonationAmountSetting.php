<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Override;

/**
 * Represents the admin setting for the default donation amount.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DefaultDonationAmountSetting implements AdminSettingInterface {

	public const string KEY = 'default_amount';

	public const int DEFAULT_VALUE = 10;

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

		return __( 'Default donation amount', 'fundrik' );
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

		return __( 'Used when the donation form block does not define its own default amount.', 'fundrik' );
	}

	/**
	 * Returns the default value for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return int Default setting value.
	 */
	#[Override]
	public function get_default_value(): int {

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

		return __( 'Default donation amount must be a positive integer.', 'fundrik' );
	}

	/**
	 * Normalizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return int|null Normalized setting value, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function normalize_value( mixed $value ): ?int {

		return $this->parse_default_donation_amount( $value );
	}

	/**
	 * Sanitizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return int|null Sanitized setting value, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function sanitize_value( mixed $value ): ?int {

		return $this->parse_default_donation_amount( $value );
	}

	/**
	 * Renders the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Rendering arguments.
	 *
	 * @phpstan-param array{
	 *     field_name: string,
	 *     input_id: string,
	 *     value: bool|float|int|string|null
	 * } $args Rendering arguments.
	 */
	#[Override]
	public function render( array $args ): void {

		$this->field_renderer->render_number_field(
			$args['field_name'],
			$args['input_id'],
			is_int( $args['value'] ) ? $args['value'] : 0,
			1,
			null,
			1,
		);

		echo '<p class="description">' . esc_html( $this->get_description() ) . '</p>';
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
}
