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

		$amount_input_id = sprintf( 'fundrik-donation-amount-%d', $data->campaign_id );

		$parts = [];

		$parts[] = $this->render_form_open( $data );
		$parts[] = $this->render_amount_label( $amount_input_id, $data->amount_label );
		$parts[] = $this->render_amount_input( $amount_input_id, $data->default_amount );
		$parts[] = $this->render_submit_button();
		$parts[] = $this->render_message_markup();
		$parts[] = $this->render_form_close();

		return implode( '', $parts );
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
			. ' data-rest-url="%1$s"'
			. ' data-campaign-id="%2$d"'
			. ' data-donation-id="%3$s"'
			. '>',
			esc_url( $data->rest_url ),
			$data->campaign_id,
			esc_attr( $data->donation_id ),
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
			'<label class="fundrik-donation-form__label" for="%1$s">%2$s</label>',
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
			. ' id="%1$s"'
			. ' class="fundrik-donation-form__amount"'
			. ' type="number"'
			. ' name="amount"'
			. ' min="1"'
			. ' step="1"'
			. ' inputmode="numeric"'
			. ' value="%2$d"'
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

		return '<p class="fundrik-donation-form__message" aria-live="polite"></p>';
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
