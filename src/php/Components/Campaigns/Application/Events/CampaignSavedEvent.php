<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Signals that a campaign has been created or updated.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignSavedEvent {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id The ID of the saved campaign.
	 * @param bool $is_update Whether the campaign was updated (true) or created (false).
	 */
	public function __construct(
		public EntityId $campaign_id,
		public bool $is_update,
	) {}
}
