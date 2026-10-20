<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Presentation\Renderers\CampaignSummary;

use Fundrik\Toolbox\ArrayValidator;
use Fundrik\WordPress\Presentation\Formatters\MoneyFormatter;

/**
 * Renders HTML markup for the campaign summary.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignSummaryRenderer {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param MoneyFormatter $money_formatter Formats money amounts for public display.
	 */
	public function __construct(
		private MoneyFormatter $money_formatter,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the campaign summary markup for the given render data.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignSummaryRenderData $data Campaign summary render data.
	 *
	 * @return string Rendered campaign summary markup.
	 */
	public function render( CampaignSummaryRenderData $data ): string {

		$parts = [
			'wrapper_open' => $this->render_wrapper_open( $data ),
			'status' => $this->render_status( $data ),
			'metrics' => $this->render_metrics( $data ),
			'wrapper_close' => $this->render_wrapper_close(),
		];

		/**
		 * Filters the campaign summary markup fragments.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $parts Campaign summary markup parts keyed by fragment name.
		 * @param CampaignSummaryRenderData $data Campaign summary render data.
		 */
		$filtered_parts = apply_filters( 'fundrik_campaign_summary_markup_parts', $parts, $data );

		$parts = $this->ensure_markup_parts_filter_result( $filtered_parts, $parts );

		$markup = implode( '', $parts );

		/**
		 * Filters the rendered campaign summary markup.
		 *
		 * @since 1.0.0
		 *
		 * @param string $markup Campaign summary markup.
		 * @param CampaignSummaryRenderData $data Campaign summary render data.
		 * @param array<string, string> $parts Campaign summary markup parts keyed by fragment name.
		 *
		 * @return string Filtered campaign summary markup.
		 */
		$filtered_markup = apply_filters( 'fundrik_campaign_summary_markup', $markup, $data, $parts );

		return $this->ensure_markup_filter_result( $filtered_markup, $markup );
	}
	// phpcs:enable

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

		if ( ! is_string( $filtered_markup ) ) {

			_doing_it_wrong( 'fundrik_campaign_summary_markup', 'Filter must return a string.', '1.0.0' );

			return $markup;
		}

		return $filtered_markup;
	}

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

		if ( ! ArrayValidator::is_array_with_string_keys_and_values( $filtered_parts ) ) {

			_doing_it_wrong(
				'fundrik_campaign_summary_markup_parts',
				'Filter must return an array of string markup parts.',
				'1.0.0',
			);

			return $parts;
		}

		return $filtered_parts;
	}

	/**
	 * Returns the opening wrapper markup.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignSummaryRenderData $data Campaign summary render data.
	 *
	 * @return string Rendered opening wrapper markup.
	 */
	private function render_wrapper_open( CampaignSummaryRenderData $data ): string {

		return sprintf(
			'<section class="fundrik-campaign-summary" data-campaign-id="%d" data-status="%s">',
			$data->campaign_id,
			esc_attr( $data->campaign_status ),
		);
	}

	/**
	 * Returns the status markup.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignSummaryRenderData $data Campaign summary render data.
	 *
	 * @return string Rendered status markup.
	 */
	private function render_status( CampaignSummaryRenderData $data ): string {

		if ( ! $data->show_status ) {
			return '';
		}

		return sprintf(
			'<div class="fundrik-campaign-summary__status" data-status="%s">%s</div>',
			esc_attr( $data->campaign_status ),
			esc_html( $this->resolve_status_label( $data->campaign_status ) ),
		);
	}

	/**
	 * Returns the metrics grid markup.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignSummaryRenderData $data Campaign summary render data.
	 *
	 * @return string Rendered metrics grid markup.
	 */
	private function render_metrics( CampaignSummaryRenderData $data ): string {

		$metrics = [];

		if ( $data->show_collected_amount ) {
			$metrics[] = $this->render_collected_metric( $data );
		}

		if ( $data->show_goal && $data->target_amount !== null ) {
			$metrics[] = $this->render_goal_metric( $data );
		}

		if ( $data->show_donations_count ) {
			$metrics[] = $this->render_donations_metric( $data );
		}

		return sprintf(
			'<div class="fundrik-campaign-summary__metrics">%s</div>',
			implode( '', $metrics ),
		);
	}

	/**
	 * Returns the collected amount metric markup.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignSummaryRenderData $data Campaign summary render data.
	 *
	 * @return string Rendered collected amount metric markup.
	 */
	private function render_collected_metric( CampaignSummaryRenderData $data ): string {

		return $this->render_metric(
			'collected',
			__( 'Collected', 'fundrik' ),
			$this->money_formatter->format( $data->collected_amount, $data->currency_code ),
		);
	}

	/**
	 * Returns the goal metric markup.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignSummaryRenderData $data Campaign summary render data.
	 *
	 * @return string Rendered goal metric markup.
	 */
	private function render_goal_metric( CampaignSummaryRenderData $data ): string {

		return $this->render_metric(
			'goal',
			__( 'Goal', 'fundrik' ),
			$this->money_formatter->format( $data->target_amount ?? 0, $data->currency_code ),
		);
	}

	/**
	 * Returns the donations count metric markup.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignSummaryRenderData $data Campaign summary render data.
	 *
	 * @return string Rendered donations count metric markup.
	 */
	private function render_donations_metric( CampaignSummaryRenderData $data ): string {

		return $this->render_metric(
			'donations',
			__( 'Donations', 'fundrik' ),
			(string) $data->donations_count,
		);
	}

	/**
	 * Returns a single metric card markup.
	 *
	 * @since 1.0.0
	 *
	 * @param string $metric_key Metric key.
	 * @param string $label Metric label.
	 * @param string $value Metric value.
	 *
	 * @return string Rendered metric card markup.
	 */
	private function render_metric( string $metric_key, string $label, string $value ): string {

		return sprintf(
			'<div class="fundrik-campaign-summary__metric" data-metric="%s">'
			. '<span class="fundrik-campaign-summary__metric-label">%s</span>'
			. '<strong class="fundrik-campaign-summary__metric-value">%s</strong>'
			. '</div>',
			esc_attr( $metric_key ),
			esc_html( $label ),
			esc_html( $value ),
		);
	}

	/**
	 * Returns the closing wrapper markup.
	 *
	 * @since 1.0.0
	 *
	 * @return string Rendered closing wrapper markup.
	 */
	private function render_wrapper_close(): string {

		return '</section>';
	}

	/**
	 * Returns the translated status label for the given status code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $campaign_status Campaign fundraising status.
	 *
	 * @return string Translated status label.
	 */
	private function resolve_status_label( string $campaign_status ): string {

		return match ( $campaign_status ) {
			'donations_disabled' => __( 'Donations disabled', 'fundrik' ),
			'target_reached' => __( 'Goal reached', 'fundrik' ),
			default => __( 'Fundraising in progress', 'fundrik' ),
		};
	}
}
