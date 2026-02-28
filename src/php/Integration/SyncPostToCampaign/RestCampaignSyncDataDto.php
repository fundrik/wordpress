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
final readonly class RestCampaignSyncDataDto {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The campaign ID.
	 * @param string $title The campaign title.
	 * @param EntityVersion $version The campaign version.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open for donations.
	 * @param bool $has_target Whether the campaign has a fundraising target.
	 * @param int $target_amount The fundraising target amount in minor units.
	 */
	public function __construct(
		public EntityId $id,
		public string $title,
		public EntityVersion $version,
		public bool $is_active,
		public bool $is_open,
		public bool $has_target,
		public int $target_amount,
	) {}
}
