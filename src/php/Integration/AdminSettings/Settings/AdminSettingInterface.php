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
	 * Returns the setting ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting ID.
	 */
	public function get_id(): string;

	/**
	 * Returns the label displayed for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting label.
	 */
	public function get_label(): string;

	/**
	 * Returns the default value for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|float|int|string|null Default setting value.
	 */
	public function get_default_value(): bool|float|int|string|null;

	/**
	 * Normalizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return bool|float|int|string|null Normalized setting value.
	 *
	 * @throws \InvalidArgumentException When the value is invalid.
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
	 * @return bool|float|int|string|null Sanitized setting value.
	 *
	 * @throws \InvalidArgumentException When the value is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function sanitize_value( mixed $value ): bool|float|int|string|null;

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
	public function render( array $args ): void;
}
