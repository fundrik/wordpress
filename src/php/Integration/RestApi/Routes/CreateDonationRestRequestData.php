<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\Core\Components\Shared\Domain\EntityId;

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
	 * @param EntityId $donation_id Donation identifier.
	 * @param EntityId $campaign_id Campaign identifier.
	 * @param int $amount Donation amount in minor units.
	 */
	public function __construct(
		public EntityId $donation_id,
		public EntityId $campaign_id,
		public int $amount,
	) {}
}
