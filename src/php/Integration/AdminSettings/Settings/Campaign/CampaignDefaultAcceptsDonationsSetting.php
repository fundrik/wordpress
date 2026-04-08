<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\WpSchemaType;
use Override;

/**
 * Represents the admin setting for the default accepts donations value.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignDefaultAcceptsDonationsSetting implements AdminSettingInterface {

	private const string ID = 'default_accepts_donations';

	private const bool DEFAULT_VALUE = true;

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

		return __( 'Default accepts donations', 'fundrik' );
	}

	/**
	 * Returns the default value for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Default setting value.
	 */
	#[Override]
	public function get_default_value(): bool {

		return self::DEFAULT_VALUE;
	}

	/**
	 * Returns the expected value type for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return WpSchemaType Setting value type.
	 */
	#[Override]
	public function get_value_type(): WpSchemaType {

		return WpSchemaType::Boolean;
	}

	/**
	 * Sanitizes the setting value without side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return bool Sanitized setting value.
	 */
	#[Override]
	public function sanitize_value( mixed $value ): bool {

		return (bool) $value;
	}

	/**
	 * Renders the setting control.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, int|string|bool> $args Rendering arguments.
	 *
	 * @phpstan-param array{
	 *     field_name: string,
	 *     input_id: string,
	 *     value: int|string|bool
	 * } $args
	 */
	#[Override]
	public function render( array $args ): void {

		$this->field_renderer->render_checkbox_field(
			$args['field_name'],
			$args['input_id'],
			(bool) $args['value'],
			__( 'New campaigns accept donations.', 'fundrik' ),
		);
	}
}
