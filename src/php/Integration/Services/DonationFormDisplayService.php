<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Services;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\Renderers\DonationForm\DonationFormRenderData;
use Fundrik\WordPress\Integration\Renderers\DonationForm\DonationFormRenderer;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;

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
	 * @param DonationFormRenderer $donation_form_renderer Renders donation form markup for known campaigns.
	 */
	public function __construct(
		private CampaignLookupService $campaign_lookup,
		private AdminSettingsReader $settings_reader,
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

		return $this->donation_form_renderer->render(
			$this->create_render_data( $campaign ),
		);
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
			campaign_id: CampaignId::from_entity_id_value( $campaign->get_id() )->get_value(),
			donation_id: DonationId::generate()->get_value(),
			rest_url: RestRouteDefinitions::get_route_url( DonationsRestRoute::class ),
			default_amount: $this->settings_reader->get_donation_form_default_amount(),
			amount_label: $this->settings_reader->get_donation_form_default_amount_label(),
		);
	}
}
