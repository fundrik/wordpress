<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use InvalidArgumentException;
use Override;

/**
 * Represents the admin setting for the default amount label.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DefaultAmountLabelSetting implements AdminSettingInterface {

	private const string ID = 'default_amount_label';

	private const string DEFAULT_VALUE = 'Amount';

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

		return __( 'Default amount label', 'fundrik' );
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
	 * @throws InvalidArgumentException When the value is empty.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function normalize_value( mixed $value ): string {

		return $this->parse_default_amount_label( $value );
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
	 * @throws InvalidArgumentException When the value is empty.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function sanitize_value( mixed $value ): string {

		return $this->parse_default_amount_label( $value );
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

		$this->field_renderer->render_text_field( $args['field_name'], $args['input_id'], $args['value'] );

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Used when the donation form block does not define its own amount label.', 'fundrik' ),
		);
	}

	/**
	 * Parses a non-empty default amount label.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Amount label candidate.
	 *
	 * @return string Amount label.
	 *
	 * @throws InvalidArgumentException When the value is empty.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function parse_default_amount_label( mixed $value ): string {

		$default_amount_label = trim( TypeCaster::to_string( $value ) );

		if ( $default_amount_label !== '' ) {
			return $default_amount_label;
		}

		throw new InvalidArgumentException(
			sprintf( 'Default amount label must not be empty. Given: %s.', $value ),
		);
	}
}
