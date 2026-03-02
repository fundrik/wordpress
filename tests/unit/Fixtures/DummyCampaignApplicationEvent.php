<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Test-only application event fixture with a campaign ID payload.
 */
final readonly class DummyCampaignApplicationEvent implements ApplicationEventInterface {

	/**
	 * Constructor.
	 *
	 * @param EntityId $campaign_id The campaign ID payload.
	 */
	public function __construct(
		public EntityId $campaign_id,
	) {}
}
