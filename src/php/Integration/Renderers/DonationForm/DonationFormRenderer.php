<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Renderers\DonationForm;

/**
 * Renders HTML markup for the donation form.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormRenderer {

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
		 */
		$parts = apply_filters( 'fundrik_donation_form_markup_parts', $parts, $data );

		$markup = implode( '', $parts );

		/**
		 * Filters the rendered donation form markup.
		 *
		 * @since 1.0.0
		 *
		 * @param string $markup Donation form markup.
		 * @param DonationFormRenderData $data Donation form render data.
		 * @param array<string, string> $parts Donation form markup parts keyed by fragment name.
		 */
		return apply_filters( 'fundrik_donation_form_markup', $markup, $data, $parts );
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
			. ' data-rest-url="%s"'
			. ' data-campaign-id="%d"'
			. '>',
			esc_url( $data->rest_url ),
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
			. ' min="1"'
			. ' step="1"'
			. ' inputmode="numeric"'
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
