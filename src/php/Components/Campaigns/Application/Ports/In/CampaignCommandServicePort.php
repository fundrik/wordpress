<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Ports\In;

use Fundrik\Core\Components\Shared\Domain\EntityId;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;

/**
 * Defines the inbound port interface for application-level operations for managing campaigns.
 *
 * @since 0.1.0
 */
interface CampaignCommandServicePort {

	/**
	 * Creates a new campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to create.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository insert fails.
	 */
	public function create_campaign( Campaign $campaign ): void;

	/**
	 * Updates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository update fails.
	 */
	public function update_campaign( Campaign $campaign ): void;

	/**
	 * Saves the given campaign.
	 *
	 * Creates a new campaign or updates an existing one.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository save fails.
	 */
	public function save_campaign( Campaign $campaign ): void;

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository delete fails.
	 */
	public function delete_campaign( EntityId $id ): void;
}
