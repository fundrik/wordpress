<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings;

/**
 * Provides rendering for primitive admin settings fields.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AdminSettingsFieldRenderer {

	/**
	 * Renders a number input field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name HTML field name.
	 * @param string $input_id HTML input ID.
	 * @param int $value Current field value.
	 * @param int|null $min Minimum accepted value, if configured.
	 * @param int|null $max Maximum accepted value, if configured.
	 * @param int|null $step Step attribute value, if configured.
	 */
	public function render_number_field(
		string $field_name,
		string $input_id,
		int $value,
		?int $min = null,
		?int $max = null,
		?int $step = null,
	): void {

		printf(
			'<input type="number" name="%s" class="regular-text" id="%s" value="%d"%s>',
			esc_attr( $field_name ),
			esc_attr( $input_id ),
			(int) $value,
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Input attributes are escaped before markup output.
			$this->build_optional_number_attributes( $min, $max, $step ),
		);
	}

	/**
	 * Renders a text input field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_name HTML field name.
	 * @param string $input_id HTML input ID.
	 * @param string $value Current field value.
	 * @param int|null $maxlength Maximum accepted length, if configured.
	 */
	public function render_text_field(
		string $field_name,
		string $input_id,
		string $value,
		?int $maxlength = null,
	): void {

		$maxlength_attribute = '';

		if ( $maxlength !== null ) {
			$maxlength_attribute = sprintf( ' maxlength="%d"', (int) $maxlength );
		}

		printf(
			'<input type="text" name="%s" class="regular-text" id="%s" value="%s"%s>',
			esc_attr( $field_name ),
			esc_attr( $input_id ),
			esc_attr( $value ),
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Input attributes are escaped before markup output.
			$maxlength_attribute,
		);
	}

	/**
	 * Builds optional HTML attributes for a number input field.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $min Minimum accepted value, if configured.
	 * @param int|null $max Maximum accepted value, if configured.
	 * @param int|null $step Step attribute value, if configured.
	 *
	 * @return string HTML attributes markup.
	 */
	private function build_optional_number_attributes( ?int $min, ?int $max, ?int $step ): string {

		$attributes = '';

		if ( $min !== null ) {
			$attributes .= sprintf( ' min="%d"', (int) $min );
		}

		if ( $max !== null ) {
			$attributes .= sprintf( ' max="%d"', (int) $max );
		}

		if ( $step !== null ) {
			$attributes .= sprintf( ' step="%d"', (int) $step );
		}

		return $attributes;
	}
}
