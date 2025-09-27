<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Ports\In;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;

/**
 * Defines the inbound port interface for application-level operations for managing WordPress campaigns.
 *
 * @since 1.0.0
 */
interface CampaignServicePortInterface {

	/**
	 * Retrieves a WordPress-specific campaign by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return Campaign|null The WordPress campaign if found, otherwise null.
	 */
	public function find_campaign_by_id( EntityId $id ): ?Campaign;

	/**
	 * Retrieves all WordPress-specific campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @return array<Campaign> All available WordPress campaign entities.
	 */
	public function find_all_campaigns(): array;

	/**
	 * Saves the given WordPress campaign.
	 *
	 * Creates a new campaign or updates an existing one.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign The WordPress campaign to save.
	 */
	public function save_campaign( Campaign $campaign ): void;

	/**
	 * Deletes a WordPress campaign by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The ID of the WordPress campaign to delete.
	 */
	public function delete_campaign( EntityId $id ): void;
}
