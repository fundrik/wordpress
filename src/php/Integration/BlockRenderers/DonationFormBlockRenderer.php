<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\BlockRenderers;

use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettings;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Ramsey\Uuid\Uuid;

/**
 * Renders HTML markup for the donation form block.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormBlockRenderer {

	private const string REST_ROUTE = DonationsRestRoute::ROUTE_NAMESPACE . DonationsRestRoute::ROUTE_PATH;

	/**
	 * Renders the donation form block markup.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, bool|float|int|string|null> $attributes Block attributes from block rendering context.
	 *
	 * @return string Rendered HTML markup.
	 */
	public function render( array $attributes = [] ): string {

		$post_id = $this->resolve_post_id();

		if ( $post_id <= 0 ) {
			return $this->render_unavailable_markup();
		}

		return $this->render_available_form( $post_id, $attributes );
	}

	/**
	 * Resolves the current campaign identifier from the loop or queried object.
	 *
	 * @since 1.0.0
	 */
	private function resolve_post_id(): int {

		$post_id = get_the_ID();

		if ( ! is_int( $post_id ) || $post_id <= 0 ) {
			return (int) get_queried_object_id();
		}

		return $post_id;
	}

	/**
	 * Resolves the donation ID used by the frontend to deduplicate form submission retries.
	 *
	 * @since 1.0.0
	 */
	private function resolve_donation_id(): string {

		$donation_id = wp_generate_uuid4();

		if ( ! is_string( $donation_id ) || $donation_id === '' ) {
			return Uuid::uuid4()->toString();
		}

		return $donation_id;
	}

	/**
	 * Renders the unavailable-state markup when the block is used outside a campaign context.
	 *
	 * @since 1.0.0
	 */
	private function render_unavailable_markup(): string {

		return sprintf(
			'<div class="wp-block-fundrik-donation-form"><p>%s</p></div>',
			esc_html__( 'Donation form is unavailable on this page.', 'fundrik' ),
		);
	}

	/**
	 * Renders the donation form when a campaign post is available.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Campaign post identifier.
	 * @param array<string, bool|float|int|string|null> $attributes Block attributes from block rendering context.
	 */
	private function render_available_form( int $post_id, array $attributes ): string {

		return $this->render_form_markup(
			$post_id,
			$this->resolve_donation_id(),
			$this->resolve_amount_field_markup( $post_id, $attributes ),
		);
	}

	/**
	 * Resolves the amount field markup from block attributes and saved defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Campaign post identifier.
	 * @param array<string, bool|float|int|string|null> $attributes Block attributes from block rendering context.
	 *
	 * @return string Prepared amount field markup.
	 */
	private function resolve_amount_field_markup( int $post_id, array $attributes ): string {

		$amount_input_id = sprintf( 'fundrik-donation-amount-%d', $post_id );
		$default_form_settings = $this->get_default_form_settings();
		$default_amount = $this->resolve_default_amount(
			$attributes['defaultAmount'] ?? null,
			$default_form_settings[ DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY ],
		);
		[
			$amount_label,
			$default_amount_label,
		] = $this->resolve_amount_labels(
			$attributes['amountLabel'] ?? null,
			$default_form_settings[ DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY ],
		);

		return $this->build_amount_field_markup(
			$amount_input_id,
			$default_amount,
			$amount_label,
			$default_amount_label,
		);
	}

	/**
	 * Builds the label and amount input markup for the donation form.
	 *
	 * @since 1.0.0
	 *
	 * @param string $amount_input_id HTML id attribute for the amount field.
	 * @param int $default_amount Default amount shown in the form.
	 * @param string|null $amount_label Optional visible amount label.
	 * @param string $default_amount_label Fallback accessibility label.
	 */
	private function build_amount_field_markup(
		string $amount_input_id,
		int $default_amount,
		?string $amount_label,
		string $default_amount_label,
	): string {

		$amount_label_markup = '';
		$amount_aria_label_attribute = '';

		if ( $amount_label === null ) {
			$amount_aria_label_attribute = sprintf( ' aria-label="%s"', esc_attr( $default_amount_label ) );
		} else {
			$amount_label_markup = sprintf(
				'<label class="fundrik-donation-form__label" for="%1$s">%2$s</label>',
				esc_attr( $amount_input_id ),
				esc_html( $amount_label ),
			);
		}

		return sprintf(
			'%1$s<input id="%2$s" class="fundrik-donation-form__amount"'
			. ' type="number" name="amount" min="1" step="1"'
			. ' inputmode="numeric" value="%3$d"%4$s required />',
			$amount_label_markup,
			esc_attr( $amount_input_id ),
			$default_amount,
			$amount_aria_label_attribute,
		);
	}

	/**
	 * Renders the complete donation form markup.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Campaign post identifier.
	 * @param string $donation_id Client-visible donation request identifier.
	 * @param string $amount_field_markup Prepared amount label and input markup.
	 */
	private function render_form_markup( int $post_id, string $donation_id, string $amount_field_markup ): string {

		return sprintf(
			'<div class="wp-block-fundrik-donation-form"><form class="fundrik-donation-form"'
			. ' data-rest-url="%1$s"'
			. ' data-campaign-id="%2$d"'
			. ' data-donation-id="%3$s">%4$s'
			. '<button class="fundrik-donation-form__submit" type="submit">%5$s</button>'
			. '<p class="fundrik-donation-form__message" aria-live="polite"></p>'
			. '</form></div>',
			esc_url( rest_url( self::REST_ROUTE ) ),
			$post_id,
			esc_attr( $donation_id ),
			$amount_field_markup,
			esc_html__( 'Donate', 'fundrik' ),
		);
	}

	/**
	 * Resolves default donation amount from block attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|float|int|string|null $default_amount_value Raw block attribute value.
	 * @param int $saved_default_amount Saved plugin default amount.
	 *
	 * @return int Positive integer amount.
	 */
	private function resolve_default_amount(
		bool|float|int|string|null $default_amount_value,
		int $saved_default_amount,
	): int {

		$default_amount_candidate = filter_var(
			$default_amount_value,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 1,
				],
			],
		);

		if ( $default_amount_candidate !== false ) {
			return $default_amount_candidate;
		}

		return $saved_default_amount;
	}

	/**
	 * Returns the normalized donation form defaults from plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array{default_amount: int, default_amount_label: string} Donation form defaults.
	 */
	private function get_default_form_settings(): array {

		$stored_settings = get_option(
			DonationFormSettings::OPTION_NAME,
			$this->get_default_form_settings_defaults(),
		);
		$settings = is_array( $stored_settings ) ? $stored_settings : [];

		return [
			DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => $this->normalize_saved_default_amount( $settings ),
			DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => $this->normalize_saved_default_amount_label( $settings ),
		];
	}

	/**
	 * Returns the fallback donation form defaults used for a missing option row.
	 *
	 * @since 1.0.0
	 *
	 * @return array{default_amount: int, default_amount_label: string} Fallback donation form defaults.
	 */
	private function get_default_form_settings_defaults(): array {

		return [
			DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY => DonationFormSettings::DEFAULT_DONATION_AMOUNT_DEFAULT,
			DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY => DonationFormSettings::DEFAULT_AMOUNT_LABEL_DEFAULT,
		];
	}

	/**
	 * Returns the normalized saved default donation amount.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings Saved donation form settings.
	 *
	 * @return int Positive integer amount.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function normalize_saved_default_amount( array $settings ): int {

		$default_amount = filter_var(
			$settings[ DonationFormSettings::DEFAULT_DONATION_AMOUNT_KEY ] ?? null,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 1,
				],
			],
		);

		if ( $default_amount !== false ) {
			return $default_amount;
		}

		return DonationFormSettings::DEFAULT_DONATION_AMOUNT_DEFAULT;
	}

	/**
	 * Returns the normalized saved default amount label.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings Saved donation form settings.
	 *
	 * @return string Non-empty amount label.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function normalize_saved_default_amount_label( array $settings ): string {

		$default_amount_label = is_string( $settings[ DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY ] ?? null )
			? trim( $settings[ DonationFormSettings::DEFAULT_AMOUNT_LABEL_KEY ] )
			: '';

		if ( $default_amount_label !== '' ) {
			return $default_amount_label;
		}

		return DonationFormSettings::DEFAULT_AMOUNT_LABEL_DEFAULT;
	}

	/**
	 * Resolves the visible and accessibility amount labels.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|float|int|string|null $amount_label_value Raw block attribute value.
	 * @param string $saved_default_amount_label Saved plugin default amount label.
	 *
	 * @return array{0: string|null, 1: string} Visible label and accessibility label.
	 */
	private function resolve_amount_labels(
		bool|float|int|string|null $amount_label_value,
		string $saved_default_amount_label,
	): array {

		if ( $amount_label_value === null || ! is_string( $amount_label_value ) ) {
			return [ $saved_default_amount_label, $saved_default_amount_label ];
		}

		$amount_label_candidate = trim( $amount_label_value );

		if ( $amount_label_candidate === '' ) {
			return [ null, $saved_default_amount_label ];
		}

		return [ $amount_label_candidate, $amount_label_candidate ];
	}
}
