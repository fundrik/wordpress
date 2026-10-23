<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways\SettingFields;

use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Represents a gateway settings field definition.
 *
 * @template TValue of int|string|bool = int|string|bool
 *
 * @since 1.0.0
 */
interface GatewaySettingFieldInterface {

	/**
	 * Returns the field ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Field ID.
	 */
	public function get_id(): string;

	/**
	 * Returns the field label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Field label.
	 */
	public function get_label(): string;

	/**
	 * Returns the default field value.
	 *
	 * @since 1.0.0
	 *
	 * @return TValue Default field value.
	 */
	public function get_default_value(): int|string|bool;

	/**
	 * Returns the field value type.
	 *
	 * @since 1.0.0
	 *
	 * @return WpSchemaType Field value type.
	 */
	public function get_value_type(): WpSchemaType;

	/**
	 * Renders the field control.
	 *
	 * @since 1.0.0
	 *
	 * @param array{field_name: string, input_id: string, value: TValue} $args Rendering arguments.
	 */
	public function render( array $args ): void;
}
