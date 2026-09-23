<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways\SettingFields;

use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use Override;

/**
 * Represents a checkbox gateway settings field definition.
 *
 * @since 1.0.0
 */
final readonly class GatewaySettingCheckboxField implements GatewaySettingFieldInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Field ID.
	 * @param string $label Field label.
	 * @param bool $default_value Default field value.
	 */
	public function __construct(
		private string $id,
		private string $label,
		private bool $default_value,
	) {}

	/**
	 * Returns the field ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Field ID.
	 */
	#[Override]
	public function get_id(): string {

		return $this->id;
	}

	/**
	 * Returns the field label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Field label.
	 */
	#[Override]
	public function get_label(): string {

		return $this->label;
	}

	/**
	 * Returns the default field value.
	 *
	 * @since 1.0.0
	 *
	 * @return int|string|bool Default field value.
	 */
	#[Override]
	public function get_default_value(): int|string|bool {

		return $this->default_value;
	}

	/**
	 * Returns the field value type.
	 *
	 * @since 1.0.0
	 *
	 * @return WpSchemaType Field value type.
	 */
	#[Override]
	public function get_value_type(): WpSchemaType {

		return WpSchemaType::Boolean;
	}

	/**
	 * Renders the checkbox field control.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int|string|bool> $args Rendering arguments.
	 *
	 * @phpstan-param array{
	 *     field_name: string,
	 *     input_id: string,
	 *     value: bool
	 * } $args
	 */
	#[Override]
	public function render( array $args ): void {

		SettingFieldRenderer::render_checkbox_field(
			$args['field_name'],
			$args['input_id'],
			$args['value'],
			$this->label,
		);
	}
}
