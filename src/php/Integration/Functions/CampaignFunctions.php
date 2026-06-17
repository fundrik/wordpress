<?php

declare(strict_types=1);

use Fundrik\WordPress\Integration\Campaign;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;

/**
 * Returns a campaign by ID or current campaign post.
 *
 * @since 1.0.0
 *
 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
 *
 * @return Campaign|null Campaign if found, null otherwise.
 */
function fundrik_get_campaign( ?int $campaign_id = null ): ?Campaign {

	return RuntimeContainer::get()
		->make( CampaignLookupService::class )
		->get( $campaign_id );
}
