<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;

/**
 * Carries the normalized campaign synchronization data for REST-based requests.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestCampaignSyncData {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The campaign ID.
	 * @param string $title The campaign title.
	 * @param EntityVersion $version The campaign version.
	 * @param bool $accepts_donations Whether the campaign accepts donations.
	 * @param bool $has_target Whether the campaign has a fundraising target.
	 * @param int|null $target_amount The fundraising target amount in minor units, if configured.
	 * @param string $target_currency The fundraising target currency (ISO 4217).
	 */
	public function __construct(
		public EntityId $id,
		public string $title,
		public EntityVersion $version,
		public bool $accepts_donations,
		public bool $has_target,
		public ?int $target_amount,
		public string $target_currency,
	) {}
}
