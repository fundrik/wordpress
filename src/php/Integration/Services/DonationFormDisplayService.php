<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Services;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\Gateways\GatewayResolutionException;
use Fundrik\WordPress\Integration\Gateways\GatewayResolver;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Presentation\Renderers\DonationForm\DonationFormRenderData;
use Fundrik\WordPress\Presentation\Renderers\DonationForm\DonationFormRenderer;

/**
 * Provides donation form display for WordPress-facing integration entry points.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationFormDisplayService {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignLookupService $campaign_lookup Provides campaign lookup for donation form display.
	 * @param AdminSettingsReader $settings_reader Provides resolved admin settings values.
	 * @param GatewayResolver $gateway_resolver Resolves the active gateway.
	 * @param DonationFormRenderer $donation_form_renderer Renders donation form markup for known campaigns.
	 */
	public function __construct(
		private CampaignLookupService $campaign_lookup,
		private AdminSettingsReader $settings_reader,
		private GatewayResolver $gateway_resolver,
		private DonationFormRenderer $donation_form_renderer,
	) {}

	/**
	 * Returns the donation form markup for the given campaign or current campaign post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
	 *
	 * @return string Rendered donation form markup.
	 */
	public function render( ?int $campaign_id = null ): string {

		$campaign = $this->campaign_lookup->get( $campaign_id );

		if ( $campaign === null ) {
			return '';
		}

		if ( ! $campaign->accepts_donations() ) {
			return '';
		}

		try {
			$this->gateway_resolver->resolve_active_gateway();
		} catch ( GatewayResolutionException ) {
			return '';
		}

		$render_data = $this->create_render_data( $campaign );

		/**
		 * Filters the donation form render data.
		 *
		 * @since 1.0.0
		 *
		 * @param DonationFormRenderData $render_data Donation form render data.
		 * @param Campaign $campaign Campaign.
		 */
		$filtered_render_data = apply_filters( 'fundrik_donation_form_render_data', $render_data, $campaign );

		$render_data = $this->ensure_render_data_filter_result( $filtered_render_data, $render_data );

		return $this->donation_form_renderer->render( $render_data );
	}

	/**
	 * Ensures the filter returned donation form render data.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $filtered_render_data Filter result.
	 * @param DonationFormRenderData $render_data Original render data.
	 *
	 * @return DonationFormRenderData Filtered render data, or original render data when the result is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function ensure_render_data_filter_result(
		mixed $filtered_render_data,
		DonationFormRenderData $render_data,
	): DonationFormRenderData {

		if ( $filtered_render_data instanceof DonationFormRenderData ) {
			return $filtered_render_data;
		}

		_doing_it_wrong(
			'fundrik_donation_form_render_data',
			sprintf(
				'Filter must return donation form render data. Given: %s.',
				esc_html( get_debug_type( $filtered_render_data ) ),
			),
			'1.0.0',
		);

		return $render_data;
	}

	/**
	 * Creates render-ready data for the donation form markup.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign Campaign.
	 *
	 * @return DonationFormRenderData Donation form render data.
	 */
	private function create_render_data( Campaign $campaign ): DonationFormRenderData {

		return new DonationFormRenderData(
			campaign_id: $campaign->get_id(),
			checkout_url: RestRouteDefinitions::get_route_url( DonationCheckoutRestRoute::class ),
			default_amount: $this->settings_reader->get_donation_form_default_amount(),
			amount_label: $this->settings_reader->get_donation_form_default_amount_label(),
		);
	}
}
