<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Ports\In;

// phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;

// phpcs:enable

/**
 * Defines the inbound port interface for application-level operations for retrieving campaigns.
 *
 * @since 0.1.0
 */
interface CampaignQueryServicePort {

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return Campaign|null The campaign if found, otherwise null.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository lookup fails.
	 * @throws CampaignAssemblerException When the DTO cannot be converted into a Campaign.
	 */
	public function find_campaign_by_id( EntityId $id ): ?Campaign;

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> All available campaign entities.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository lookup fails.
	 * @throws CampaignAssemblerException When a DTO cannot be converted into a Campaign.
	 */
	public function find_all_campaigns(): array;
}
