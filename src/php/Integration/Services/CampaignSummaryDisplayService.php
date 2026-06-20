<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Services;

use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignStatusPolicy;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Presentation\Renderers\CampaignSummary\CampaignSummaryRenderData;
use Fundrik\WordPress\Presentation\Renderers\CampaignSummary\CampaignSummaryRenderer;

/**
 * Provides campaign summary display for WordPress-facing integration entry points.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignSummaryDisplayService {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignLookupService $campaign_lookup Provides campaign lookup for campaign summary display.
	 * @param CampaignStatusPolicy $campaign_status_policy Resolves campaign fundraising status.
	 * @param CampaignSummaryRenderer $campaign_summary_renderer Renders campaign summary markup for known campaigns.
	 */
	public function __construct(
		private CampaignLookupService $campaign_lookup,
		private CampaignStatusPolicy $campaign_status_policy,
		private CampaignSummaryRenderer $campaign_summary_renderer,
	) {}

	/**
	 * Returns the campaign summary markup for the given campaign or current campaign post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
	 * @param array<string, mixed> $display_options Campaign summary display options.
	 *
	 * @return string Rendered campaign summary markup.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function render( ?int $campaign_id = null, array $display_options = [] ): string {

		$campaign = $this->campaign_lookup->get( $campaign_id );

		if ( $campaign === null ) {
			return '';
		}

		$render_data = $this->create_render_data( $campaign, $display_options );

		/**
		 * Filters the campaign summary render data.
		 *
		 * @since 1.0.0
		 *
		 * @param CampaignSummaryRenderData $render_data Campaign summary render data.
		 * @param Campaign $campaign Campaign.
		 */
		$render_data = apply_filters( 'fundrik_campaign_summary_render_data', $render_data, $campaign );

		return $this->campaign_summary_renderer->render( $render_data );
	}

	/**
	 * Creates render-ready data for the campaign summary markup.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign Campaign.
	 * @param array<string, mixed> $display_options Campaign summary display options.
	 *
	 * @return CampaignSummaryRenderData Campaign summary render data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function create_render_data( Campaign $campaign, array $display_options ): CampaignSummaryRenderData {

		$target_amount = $campaign->get_target_amount();

		return new CampaignSummaryRenderData(
			campaign_id: $campaign->get_id(),
			campaign_status: $this->campaign_status_policy->resolve(
				$campaign->accepts_donations(),
				$campaign->get_target_amount(),
				$campaign->get_collected_amount(),
			),
			currency_code: $campaign->get_currency_code(),
			collected_amount: $campaign->get_collected_amount(),
			target_amount: $target_amount,
			donations_count: $campaign->get_donations_count(),
			// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong
			show_status: ArrayExtractor::extract_bool_optional( $display_options, 'showStatus' ) ?? true,
			show_goal: ArrayExtractor::extract_bool_optional( $display_options, 'showGoal' ) ?? true,
			show_collected_amount: ArrayExtractor::extract_bool_optional( $display_options, 'showCollectedAmount' ) ?? true,
			show_donations_count: ArrayExtractor::extract_bool_optional( $display_options, 'showDonationsCount' ) ?? true,
			// phpcs:enable
		);
	}
}
