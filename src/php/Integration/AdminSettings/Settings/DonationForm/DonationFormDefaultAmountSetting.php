<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminSettings\Settings\AdminSettingInterface;
use Fundrik\WordPress\Integration\Helpers\SettingFieldRenderer;
use Fundrik\WordPress\Integration\WpSchemaType;
use InvalidArgumentException;
use Override;

/**
 * Represents the admin setting for the default donation amount.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormDefaultAmountSetting implements AdminSettingInterface {

	private const string ID = 'default_amount';

	private const int DEFAULT_VALUE = 10;

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

		return __( 'Default donation amount', 'fundrik' );
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
	 * Returns the expected value type for the setting.
	 *
	 * @since 1.0.0
	 *
	 * @return WpSchemaType Setting value type.
	 */
	#[Override]
	public function get_value_type(): WpSchemaType {

		return WpSchemaType::Integer;
	}

	/**
	 * Sanitizes the setting value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw setting value.
	 *
	 * @return int Sanitized setting value.
	 *
	 * @throws InvalidArgumentException When the value is not a positive integer.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function sanitize_value( mixed $value ): int {

		$default_amount = TypeCaster::to_int( $value );

		if ( $default_amount > 0 ) {
			return $default_amount;
		}

		throw new InvalidArgumentException(
			sprintf( 'Default donation amount must be a positive integer. Given: %d.', $value ),
		);
	}

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
	 *     value: int
	 * } $args
	 */
	#[Override]
	public function render( array $args ): void {

		SettingFieldRenderer::render_number_field(
			$args['field_name'],
			$args['input_id'],
			$args['value'],
			min: 1,
			step: 1,
		);
	}
}
