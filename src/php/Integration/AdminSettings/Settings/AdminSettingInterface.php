<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings;

/**
 * Represents a single setting exposed within an admin settings group.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface AdminSettingInterface {

	/**
	 * Returns the settings array key.
	 *
	 * @since 1.0.0
	 *
	 * @return string Settings key.
	 */
	public function get_key(): string;

	/**
	 * Returns the label displayed for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting label.
	 */
	public function get_label(): string;

	/**
	 * Returns the description displayed below the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting description.
	 */
	public function get_description(): string;

	/**
	 * Returns the default value for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|float|int|string|null Default setting value.
	 */
	public function get_default_value(): bool|float|int|string|null;

	/**
	 * Returns the validation error message for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string Validation error message.
	 */
	public function get_validation_error_message(): string;

	/**
	 * Normalizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return bool|float|int|string|null Normalized setting value, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function normalize_value( mixed $value ): bool|float|int|string|null;

	/**
	 * Sanitizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return bool|float|int|string|null Sanitized setting value, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function sanitize_value( mixed $value ): bool|float|int|string|null;

	/**
	 * Renders the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @param array{
	 *     field_name: string,
	 *     input_id: string,
	 *     value: bool|float|int|string|null
	 * } $args Rendering arguments.
	 */
	public function render( array $args ): void;
}
