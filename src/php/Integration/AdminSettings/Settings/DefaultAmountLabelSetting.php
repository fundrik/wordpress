<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Override;

/**
 * Represents the admin setting for the default amount label.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DefaultAmountLabelSetting implements AdminSettingInterface {

	public const string KEY = 'default_amount_label';

	public const string DEFAULT_VALUE = 'Amount';

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

		return __( 'Default amount label', 'fundrik' );
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

		return __( 'Used when the donation form block does not define its own amount label.', 'fundrik' );
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

		return __( 'Default amount label must not be empty.', 'fundrik' );
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

		return $this->parse_default_amount_label( $value );
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

		return $this->parse_default_amount_label( $value );
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

		$this->field_renderer->render_text_field(
			$args['field_name'],
			$args['input_id'],
			is_string( $args['value'] ) ? $args['value'] : '',
		);

		echo '<p class="description">' . esc_html( $this->get_description() ) . '</p>';
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
