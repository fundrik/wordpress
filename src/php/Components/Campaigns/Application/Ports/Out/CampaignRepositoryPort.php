<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Ports\Out;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDto;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;

/**
 * Defines the outbound port for accessing campaign persistence.
 *
 * This interface represents the storage contract required by the application layer.
 * It allows the service layer to remain decoupled from specific infrastructure details.
 *
 * @since 0.1.0
 */
interface CampaignRepositoryPort {

	/**
	 * Fetches the DTO of a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return CampaignDto|null The campaign data if found, null otherwise.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?CampaignDto;

	/**
	 * Fetches all available campaign DTOs.
	 *
	 * @since 0.1.0
	 *
	 * @return array<CampaignDto> The list of campaign data objects.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_all(): array;

	/**
	 * Returns whether the campaign exists in storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign entity to check.
	 *
	 * @return bool True if the campaign exists.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists( Campaign $campaign ): bool;

	/**
	 * Inserts a new campaign into storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to insert.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the insert fails.
	 */
	public function insert( Campaign $campaign ): void;

	/**
	 * Updates an existing campaign in storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the update fails.
	 */
	public function update( Campaign $campaign ): void;

	/**
	 * Removes a campaign from storage by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the delete fails.
	 */
	public function delete( EntityId $id ): void;
}
