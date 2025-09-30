<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Signals that a campaign has been deleted.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignDeletedEvent {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id The ID of the deleted campaign.
	 */
	public function __construct(
		public EntityId $campaign_id,
	) {}
}
