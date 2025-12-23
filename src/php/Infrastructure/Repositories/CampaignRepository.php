<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;

/**
 * Persists and retrieves campaign entities in the storage.
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
	 * @param CampaignFactory $campaign_factory Builds Campaign entities from persistence values.
	 */
	public function __construct(
		private DatabaseInterface $db,
		private CampaignFactory $campaign_factory,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return Campaign|null The campaign if found, null otherwise.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Campaign {

		$id_int = $this->get_entity_id_as_int_or_fail( $id );

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

		return $this->map_row_to_campaign_or_fail( $row );
	}

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> The list of campaign entities.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup or mapping fails.
	 */
	public function find_all(): array {

		try {
			$rows = $this->db->get_all( self::TABLE_NAME );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException( 'Cannot fetch campaigns: persistence error.', previous: $e );
		}

		return array_map(
			fn ( array $row ): Campaign => $this->map_row_to_campaign_or_fail( $row ),
			$rows,
		);
	}

	/**
	 * Returns whether a campaign exists in storage by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to check.
	 *
	 * @return bool True if the campaign exists.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists_by_id( EntityId $id ): bool {

		$id_int = $this->get_entity_id_as_int_or_fail( $id );

		try {
			return $this->db->exists( self::TABLE_NAME, $id_int );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot check campaign existence: persistence error. Given: ID %d.', $id_int ),
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

		$data = $this->map_campaign_to_row( $campaign );
		$campaign_id = $campaign->get_id();

		try {
			$this->db->insert( self::TABLE_NAME, $data );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot insert campaign: persistence error. Given: ID %d.', $campaign_id ),
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

		$data = $this->map_campaign_to_row( $campaign );
		$campaign_id = $campaign->get_id();

		try {
			$this->db->update( self::TABLE_NAME, $data, $campaign_id );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot update campaign: persistence error. Given: ID %d.', $campaign_id ),
				previous: $e,
			);
		}
	}

	/**
	 * // TODO: fix race condition. Upsert?
	 *
	 * Saves the given campaign by inserting or updating it.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @return CampaignRepositorySaveResult The persistence outcome (Inserted or Updated).
	 *
	 * @throws CampaignRepositoryExceptionInterface When the operation fails.
	 */
	public function save( Campaign $campaign ): CampaignRepositorySaveResult {

		$entity_id = $campaign->get_entity_id();

		if ( $this->exists_by_id( $entity_id ) ) {

			$this->update( $campaign );
			return CampaignRepositorySaveResult::Updated;
		}

		$this->insert( $campaign );
		return CampaignRepositorySaveResult::Inserted;
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

		$id_int = $this->get_entity_id_as_int_or_fail( $id );

		try {
			$this->db->delete( self::TABLE_NAME, $id_int );
		} catch ( DatabaseException $e ) {

			throw new CampaignRepositoryException(
				sprintf( 'Cannot delete campaign: persistence error. Given: ID %d.', $id_int ),
				previous: $e,
			);
		}
	}

	/**
	 * Converts an entity ID to int.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The entity ID to convert.
	 *
	 * @return int The integer ID value.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the ID cannot be represented as int.
	 */
	private function get_entity_id_as_int_or_fail( EntityId $id ): int {

		try {
			return $id->get_as_int();
		} catch ( InvalidEntityIdException $e ) {

			throw new CampaignRepositoryException(
				sprintf(
					'Cannot use campaign ID in persistence: ID must be int-compatible. Given: %s.',
					$id->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Builds a Campaign entity from a persistence row.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $row The persistence row.
	 *
	 * @return Campaign The mapped campaign entity.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the row cannot be mapped.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function map_row_to_campaign_or_fail( array $row ): Campaign {

		try {

			return $this->campaign_factory->create(
				id: ArrayExtractor::extract_int_required( $row, 'id' ),
				title: ArrayExtractor::extract_string_required( $row, 'title' ),
				is_active: ArrayExtractor::extract_bool_required( $row, 'is_active' ),
				is_open: ArrayExtractor::extract_bool_required( $row, 'is_open' ),
				has_target: ArrayExtractor::extract_bool_required( $row, 'has_target' ),
				target_amount: ArrayExtractor::extract_int_required( $row, 'target_amount' ),
			);
		} catch ( CampaignFactoryException | ArrayExtractionException $e ) {

			throw new CampaignRepositoryException(
				sprintf(
					'Cannot map campaign row to entity. Given: ID %d.',
					(int) ( $row['id'] ?? -1 ),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Converts a Campaign entity into a persistence row.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to convert.
	 *
	 * @return array<string, int|string|bool> The persistence row data.
	 */
	private function map_campaign_to_row( Campaign $campaign ): array {

		return [
			'id' => $campaign->get_id(),
			'title' => $campaign->get_title(),
			'is_active' => $campaign->is_active(),
			'is_open' => $campaign->is_open(),
			'has_target' => $campaign->has_target(),
			'target_amount' => $campaign->get_target_amount(),
		];
	}
}
