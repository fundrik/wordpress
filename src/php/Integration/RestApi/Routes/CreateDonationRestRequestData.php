<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;

/**
 * Carries the normalized and validated donation creation request data.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CreateDonationRestRequestData {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationId $donation_id Donation ID.
	 * @param CampaignId $campaign_id Campaign ID.
	 * @param int $amount Donation amount in minor units.
	 */
	public function __construct(
		public DonationId $donation_id,
		public CampaignId $campaign_id,
		public int $amount,
	) {}
}
