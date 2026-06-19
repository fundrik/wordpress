<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Presentation\Renderers\CampaignSummary;

/**
 * Represents raw data for the campaign summary markup.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignSummaryRenderData {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param string $campaign_status Campaign fundraising status.
	 * @param string $currency_code Currency code.
	 * @param int $collected_amount Collected amount in minor units.
	 * @param int|null $target_amount Target amount in minor units, if configured.
	 * @param int $donations_count Donations count.
	 * @param bool $show_status True when the campaign status should be rendered.
	 * @param bool $show_goal True when the goal metric should be rendered.
	 * @param bool $show_collected_amount True when the collected amount should be rendered.
	 * @param bool $show_donations_count True when the donations count should be rendered.
	 */
	public function __construct(
		public int $campaign_id,
		public string $campaign_status,
		public string $currency_code,
		public int $collected_amount,
		public ?int $target_amount,
		public int $donations_count,
		public bool $show_status,
		public bool $show_goal,
		public bool $show_collected_amount,
		public bool $show_donations_count,
	) {}
}
