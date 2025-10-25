<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories;

use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDto;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDtoFactory;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignDtoFactoryException;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPort;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;

/**
 * Persists and retrieves campaign data.
 *
 * @since 0.1.0
 */
final readonly class CampaignRepository implements CampaignRepositoryPort {

	private const TABLE_NAME = 'fundrik_campaigns';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DatabaseInterface $db Executes database queries.
	 * @param CampaignDtoFactory $dto_factory Creates and maps CampaignDto instances.
	 */
	public function __construct(
		private DatabaseInterface $db,
		private CampaignDtoFactory $dto_factory,
	) {}

	/**
	 * Fetches the DTO of a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return CampaignDto|null The campaign data if found, null otherwise.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup or mapping fails.
	 */
	public function find_by_id( EntityId $id ): ?CampaignDto {

		$id_int = $id->get_as_int();

		try {
			$row = $this->db->get_by_id( self::TABLE_NAME, $id_int );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot fetch campaign: persistence error. Given: ID %d.', $id_int ),
				previous: $e,
			);
		}

		if ( $row === null ) {
			return null;
		}

		try {
			return $this->dto_factory->from_array( $row );
		} catch ( CampaignDtoFactoryException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot map campaign row to DTO. Given: ID %d.', $id_int ),
				previous: $e,
			);
		}
	}

	/**
	 * Fetches all available campaign DTOs.
	 *
	 * @since 0.1.0
	 *
	 * @return array<CampaignDto> The list of campaign data objects.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup or mapping fails.
	 */
	public function find_all(): array {

		try {
			$rows = $this->db->get_all( self::TABLE_NAME );
		} catch ( DatabaseException $e ) {
			throw new CampaignRepositoryException( 'Cannot fetch campaigns: persistence error.', previous: $e );
		}

		try {
			return array_map(
				fn ( array $row ): CampaignDto => $this->dto_factory->from_array( $row ),
				$rows,
			);
		} catch ( CampaignDtoFactoryException $e ) {
			throw new CampaignRepositoryException( 'Cannot map campaign rows to DTOs.', previous: $e );
		}
	}

	/**
	 * Returns whether the campaign exists in storage.
	 *
	 * Checks existence by the campaign ID only.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign entity to check.
	 *
	 * @return bool True if a matching row exists.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists( Campaign $campaign ): bool {

		$id = $campaign->get_id();

		try {
			return $this->db->exists( self::TABLE_NAME, $id );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot check campaign existence: persistence error. Given: ID %d.', $id ),
				previous: $e,
			);
		}
	}

	/**
	 * Inserts a new campaign into storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to insert.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the insert fails.
	 */
	public function insert( Campaign $campaign ): void {

		$dto = $this->dto_factory->from_campaign( $campaign );

		try {
			$this->db->insert( self::TABLE_NAME, $dto->to_array() );
		} catch ( DatabaseException $e ) {
			throw new CampaignRepositoryException(
				sprintf( 'Cannot insert campaign: persistence error. Given: ID %d.', $campaign->get_id() ),
				previous: $e,
			);
		}
	}

	/**
	 * Updates an existing campaign in storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the update fails.
	 */
	public function update( Campaign $campaign ): void {

		$campaign_id = $campaign->get_id();
		$dto = $this->dto_factory->from_campaign( $campaign );

		try {
			$this->db->update( self::TABLE_NAME, $dto->to_array(), $campaign_id );
		} catch ( DatabaseException $e ) {
			throw new CampaignRepositoryException(
				sprintf( 'Cannot update campaign: persistence error. Given: ID %d.', $campaign_id ),
				previous: $e,
			);
		}
	}

	/**
	 * Saves the given campaign by inserting or updating it.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to persist.
	 *
	 * @return CampaignRepositorySaveResult Indicates whether the campaign was inserted or updated.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the operation fails due to database or mapping errors.
	 */
	public function save( Campaign $campaign ): CampaignRepositorySaveResult {

		// TODO: Implement this method.

		$campaign = $campaign;

		return CampaignRepositorySaveResult::Updated;
	}

	/**
	 * Removes a campaign from storage by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the delete fails.
	 */
	public function delete( EntityId $id ): void {

		$id_int = $id->get_as_int();

		try {
			$this->db->delete( self::TABLE_NAME, $id_int );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot delete campaign: persistence error. Given: ID %d.', $id_int ),
				previous: $e,
			);
		}
	}
}
