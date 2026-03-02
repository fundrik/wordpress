<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Test-only application event fixture with a campaign ID payload.
 */
final readonly class DummyCampaignApplicationEvent implements CampaignApplicationEventInterface {

	/**
	 * Constructor.
	 *
	 * @param EntityId $campaign_id The campaign ID payload.
	 */
	public function __construct(
		private EntityId $campaign_id,
	) {}

	/**
	 * Returns the campaign ID associated with this event.
	 *
	 * @return EntityId The campaign ID payload.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}
}
