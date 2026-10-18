<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Presentation\Renderers\DonationForm;

use Fundrik\Toolbox\TypeCaster;
use InvalidArgumentException;

/**
 * Renders HTML markup for the donation form.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormRenderer {

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the donation form markup for the given render data.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationFormRenderData $data Donation form render data.
	 *
	 * @return string Rendered donation form markup.
	 */
	public function render( DonationFormRenderData $data ): string {

		$parts = [
			'form_open' => $this->render_form_open( $data ),
			'amount_field' => $this->render_amount_field( $data ),
			'submit_button' => $this->render_submit_button(),
			'message' => $this->render_message_markup(),
			'form_close' => $this->render_form_close(),
		];

		/**
		 * Filters the donation form markup fragments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $parts Donation form markup parts keyed by fragment name.
		 * @param DonationFormRenderData $data Donation form render data.
		 *
		 * @return array<string, string> Filtered donation form markup parts.
		 */
		$filtered_parts = apply_filters( 'fundrik_donation_form_markup_parts', $parts, $data );

		$parts = $this->ensure_markup_parts_filter_result( $filtered_parts, $parts );

		$markup = implode( '', $parts );

		/**
		 * Filters the rendered donation form markup.
		 *
		 * @since 1.0.0
		 *
		 * @param string $markup Donation form markup.
		 * @param DonationFormRenderData $data Donation form render data.
		 * @param array<string, string> $parts Donation form markup parts keyed by fragment name.
		 *
		 * @return string Filtered donation form markup.
		 */
		$filtered_markup = apply_filters( 'fundrik_donation_form_markup', $markup, $data, $parts );

		return $this->ensure_markup_filter_result( $filtered_markup, $markup );
	}
	// phpcs:enable

	/**
	 * Ensures markup parts contain only string keys and values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $filtered_parts Filter result.
	 * @param array<string, string> $parts Original markup parts.
	 *
	 * @return array<string, string> Filtered markup parts, or original parts when the result is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function ensure_markup_parts_filter_result( mixed $filtered_parts, array $parts ): array {

		if ( ! $this->is_string_map( $filtered_parts ) ) {

			_doing_it_wrong(
				'fundrik_donation_form_markup_parts',
				'Filter must return an array of string markup parts.',
				'1.0.0',
			);

			return $parts;
		}

		return $filtered_parts;
	}

	/**
	 * Checks whether a value is a string-keyed map of strings.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to check.
	 *
	 * @return bool True when the value is a string-keyed map of strings.
	 *
	 * @phpstan-assert-if-true array<string, string> $value
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function is_string_map( mixed $value ): bool {

		if ( ! is_array( $value ) ) {
			return false;
		}

		try {

			foreach ( $value as $key => $part ) {

				TypeCaster::to_string( $key );
				TypeCaster::to_string( $part );
			}
		} catch ( InvalidArgumentException ) {

			return false;
		}

		return true;
	}

	/**
	 * Ensures the filter returned a string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $filtered_markup Filter result.
	 * @param string $markup Original markup.
	 *
	 * @return string Filtered markup, or original markup when the result is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function ensure_markup_filter_result( mixed $filtered_markup, string $markup ): string {

		try {
			return TypeCaster::to_string( $filtered_markup );
		} catch ( InvalidArgumentException ) {

			_doing_it_wrong( 'fundrik_donation_form_markup', 'Filter must return a string.', '1.0.0' );

			return $markup;
		}
	}

	/**
	 * Returns the opening form tag markup.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationFormRenderData $data Donation form render data.
	 *
	 * @return string Rendered opening form tag markup.
	 */
	private function render_form_open( DonationFormRenderData $data ): string {

		return sprintf(
			'<form'
			. ' class="fundrik-donation-form"'
			. ' data-checkout-url="%s"'
			. ' data-campaign-id="%d"'
			. '>',
			esc_url( $data->checkout_url ),
			$data->campaign_id,
		);
	}

	/**
	 * Returns the amount field markup.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationFormRenderData $data Donation form render data.
	 *
	 * @return string Rendered amount field markup.
	 */
	private function render_amount_field( DonationFormRenderData $data ): string {

		$amount_input_id = sprintf( 'fundrik-donation-amount-%d', $data->campaign_id );

		return sprintf(
			'<div class="fundrik-donation-form__amount-field">%s%s</div>',
			$this->render_amount_label( $amount_input_id, $data->amount_label ),
			$this->render_amount_input( $amount_input_id, $data->default_amount ),
		);
	}

	/**
	 * Returns the amount label markup.
	 *
	 * @since 1.0.0
	 *
	 * @param string $amount_input_id HTML input ID.
	 * @param string $amount_label Visible amount label.
	 *
	 * @return string Rendered amount label markup.
	 */
	private function render_amount_label( string $amount_input_id, string $amount_label ): string {

		return sprintf(
			'<label class="fundrik-donation-form__amount-label" for="%s">%s</label>',
			esc_attr( $amount_input_id ),
			esc_html( $amount_label ),
		);
	}

	/**
	 * Returns the amount input markup.
	 *
	 * @since 1.0.0
	 *
	 * @param string $amount_input_id HTML input ID.
	 * @param int $default_amount Default donation amount.
	 *
	 * @return string Rendered amount input markup.
	 */
	private function render_amount_input( string $amount_input_id, int $default_amount ): string {

		return sprintf(
			'<input'
			. ' id="%s"'
			. ' class="fundrik-donation-form__amount-input"'
			. ' type="number"'
			. ' name="amount"'
			. ' min="0.01"'
			. ' step="0.01"'
			. ' inputmode="decimal"'
			. ' value="%d"'
			. ' required'
			. ' />',
			esc_attr( $amount_input_id ),
			$default_amount,
		);
	}

	/**
	 * Returns the submit button markup.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered submit button markup.
	 */
	private function render_submit_button(): string {

		return sprintf(
			'<button class="fundrik-donation-form__submit" type="submit">%s</button>',
			esc_html__( 'Donate', 'fundrik' ),
		);
	}

	/**
	 * Returns the live region markup.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered live region markup.
	 */
	private function render_message_markup(): string {

		return '<div class="fundrik-donation-form__message" aria-live="polite"></div>';
	}

	/**
	 * Returns the closing form tag markup.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered closing form tag markup.
	 */
	private function render_form_close(): string {

		return '</form>';
	}
}
