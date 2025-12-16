<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Ports\In;

use Fundrik\Core\Components\Shared\Domain\EntityId;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;

/**
 * Defines the inbound port interface for application-level operations for retrieving campaigns.
 *
 * @since 0.1.0
 */
interface CampaignQueryServicePort {

	/**
	 * Finds a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to find.
	 *
	 * @return Campaign|null The campaign if found, otherwise null.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository lookup fails.
	 */
	public function find_campaign_by_id( EntityId $id ): ?Campaign;

	/**
	 * Finds all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> The list of campaign entities.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository lookup fails.
	 */
	public function find_all_campaigns(): array;
}
