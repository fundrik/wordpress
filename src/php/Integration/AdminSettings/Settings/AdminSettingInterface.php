<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings;

use Fundrik\WordPress\Integration\WpSchemaType;

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
	 * @return int|string Default setting value.
	 */
	public function get_default_value(): int|string;

	/**
	 * Returns the expected value type for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return WpSchemaType Setting value type.
	 */
	public function get_value_type(): WpSchemaType;

	/**
	 * Sanitizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return int|string Sanitized setting value.
	 *
	 * @throws \InvalidArgumentException When the value is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function sanitize_value( mixed $value ): int|string;

	/**
	 * Renders the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int|string> $args Rendering arguments.
	 *
	 * @phpstan-param array{
	 *     field_name: string,
	 *     input_id: string,
	 *     value: int|string
	 * } $args
	 */
	public function render( array $args ): void;
}
