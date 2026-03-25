<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignNotFoundExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseDuplicateKeyExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Override;

/**
 * Persists and retrieves campaigns in the storage.
 *
 * @since 1.0.0
 */
final readonly class CampaignRepository implements CampaignRepositoryPort {

	private const string TABLE_NAME = 'fundrik_campaigns';
	private const string DATETIME_DB_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DatabasePort $db Executes database queries.
	 * @param CampaignFactory $campaign_factory Builds Campaign entities from persistence values.
	 */
	public function __construct(
		private DatabasePort $db,
		private CampaignFactory $campaign_factory,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The campaign ID to retrieve.
	 *
	 * @return Campaign|null The campaign if found, null otherwise.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	#[Override]
	public function find_by_id( EntityId $id ): ?Campaign {

		$id_int = $this->get_campaign_id_as_int_or_fail( $id );

		try {
			$row = $this->db->get_by_id( self::TABLE_NAME, $id_int );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new CampaignRepositoryException(
				sprintf( 'Failed to fetch campaign "%d".', $id_int ),
				previous: $e,
			);
		}

		if ( $row === null ) {
			return null;
		}

		return $this->map_row_to_campaign_or_fail( $row );
	}

	/**
	 * Returns whether a campaign exists in storage by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The campaign ID to check.
	 *
	 * @return bool True if the campaign exists.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the existence check fails.
	 */
	#[Override]
	public function exists_by_id( EntityId $id ): bool {

		$id_int = $this->get_campaign_id_as_int_or_fail( $id );

		try {
			return $this->db->exists_by_id( self::TABLE_NAME, $id_int );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new CampaignRepositoryException(
				sprintf( 'Failed to check campaign "%d" existence.', $id_int ),
				previous: $e,
			);
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Inserts a new campaign into storage.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign The campaign to insert.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the insert fails.
	 */
	#[Override]
	public function insert( Campaign $campaign ): Campaign {

		$campaign_entity_id = $campaign->get_id();
		$campaign_id_int = $this->get_campaign_id_as_int_or_fail( $campaign_entity_id );
		$version = $campaign->get_version();

		if ( ! $version->equals( EntityVersion::initial() ) ) {
			throw new CampaignRepositoryException(
				sprintf(
					'Cannot insert campaign "%d": version must be initial. Given: %d.',
					$campaign_id_int,
					$version->get_value(),
				),
			);
		}

		try {
			$this->db->insert( self::TABLE_NAME, $this->map_campaign_to_insert_row( $campaign ) );
		} catch ( DatabaseDuplicateKeyExceptionInterface $e ) {
			throw new CampaignAlreadyExistsException( $campaign_id_int, $e );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new CampaignRepositoryException(
				sprintf( 'Failed to insert campaign "%d".', $campaign_id_int ),
				previous: $e,
			);
		}

		$persisted = $this->find_by_id( $campaign_entity_id );

		if ( $persisted !== null ) {
			return $persisted;
		}

		throw new CampaignRepositoryException(
			sprintf(
				'Campaign "%d" was inserted, but fetching persisted snapshot failed.',
				$campaign_id_int,
			),
		);
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Updates an existing campaign in storage.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CampaignNotFoundExceptionInterface When the campaign does not exist.
	 * @throws CampaignRepositoryExceptionInterface When the update fails for another reason.
	 */
	#[Override]
	public function update( Campaign $campaign ): Campaign {

		$campaign_entity_id = $campaign->get_id();
		$campaign_id_int = $this->get_campaign_id_as_int_or_fail( $campaign_entity_id );

		$expected_version = $campaign->get_version();
		$new_version = $expected_version->next();

		$data = $this->map_campaign_to_update_row( $campaign );
		$data['version'] = $new_version->get_value();

		try {
			$affected = $this->db->update(
				self::TABLE_NAME,
				$data,
				[
					'id' => $campaign_id_int,
					'version' => $expected_version->get_value(),
				],
			);
		} catch ( DatabaseExceptionInterface $e ) {
			throw new CampaignRepositoryException(
				sprintf( 'Failed to update campaign "%d".', $campaign_id_int ),
				previous: $e,
			);
		}

		if ( $affected === 0 ) {

			if ( ! $this->exists_by_id( $campaign_entity_id ) ) {
				throw new CampaignNotFoundException( $campaign_id_int, 'update' );
			}

			throw new CampaignRepositoryException(
				sprintf( 'Cannot update campaign "%d": version mismatch.', $campaign_id_int ),
			);
		}

		$persisted = $this->find_by_id( $campaign_entity_id );

		if ( $persisted !== null ) {
			return $persisted;
		}

		throw new CampaignRepositoryException(
			sprintf(
				'Campaign "%d" was updated, but fetching persisted snapshot failed.',
				$campaign_id_int,
			),
		);
	}
	// phpcs:enable

	/**
	 * Removes a campaign from storage by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The campaign ID to delete.
	 *
	 * @throws CampaignNotFoundExceptionInterface When the campaign does not exist.
	 * @throws CampaignRepositoryExceptionInterface When the delete fails for another reason.
	 */
	#[Override]
	public function delete( EntityId $id ): void {

		$id_int = $this->get_campaign_id_as_int_or_fail( $id );

		if ( ! $this->exists_by_id( $id ) ) {
			throw new CampaignNotFoundException( $id_int, 'delete' );
		}

		try {
			$this->db->delete( self::TABLE_NAME, $id_int );
		} catch ( DatabaseExceptionInterface $e ) {
			throw new CampaignRepositoryException(
				sprintf( 'Failed to delete campaign "%d".', $id_int ),
				previous: $e,
			);
		}
	}

	/**
	 * Converts a campaign ID to int.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The campaign ID to convert.
	 *
	 * @return int The integer ID value.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the ID cannot be represented as int.
	 */
	private function get_campaign_id_as_int_or_fail( EntityId $id ): int {

		try {
			return $id->get_as_int();
		} catch ( InvalidEntityIdException $e ) {
			throw new CampaignRepositoryException(
				sprintf(
					'Campaign ID must be int-compatible. Given: %s.',
					(string) $id->get_value(),
				),
				previous: $e,
			);
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Builds a Campaign entity from a persistence row.
	 *
	 * @since 1.0.0
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
			return $this->campaign_factory->create_from_primitives(
				id: ArrayExtractor::extract_int_required( $row, 'id' ),
				version: ArrayExtractor::extract_int_required( $row, 'version' ),
				title: ArrayExtractor::extract_string_required( $row, 'title' ),
				accepts_donations: ArrayExtractor::extract_bool_required( $row, 'accepts_donations' ),
				currency_code: ArrayExtractor::extract_string_required( $row, 'currency_code' ),
				target_amount: ArrayExtractor::extract_int_nullable_required( $row, 'target_amount' ),
			);
		} catch ( CampaignFactoryException | ArrayExtractionException $e ) {

			$id = $row['id'] ?? null;
			$id_for_error = is_int( $id ) || is_string( $id ) ? (string) $id : '<unavailable>';

			throw new CampaignRepositoryException(
				sprintf( 'Failed to map campaign row "%s".', $id_for_error ),
				previous: $e,
			);
		}
	}
	// phpcs:enable

	/**
	 * Converts a new Campaign entity into a persistence row.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign The campaign to convert.
	 *
	 * @return array<string, int|string|bool|null> The persistence row data.
	 */
	private function map_campaign_to_insert_row( Campaign $campaign ): array {

		$created_at = $this->current_utc_timestamp();
		$target = $campaign->get_target();
		$target_amount = $target->get_amount();

		return [
			'id' => $campaign->get_id()->get_as_int(),
			'version' => $campaign->get_version()->get_value(),
			'title' => $campaign->get_title(),
			'accepts_donations' => $campaign->accepts_donations(),
			'currency_code' => $target->get_currency()->get_code(),
			'target_amount' => $target_amount?->get_value(),
			'created_at' => $created_at,
			'updated_at' => null,
		];
	}

	/**
	 * Converts a Campaign entity into persistence fields controlled by the domain model.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign The campaign to convert.
	 *
	 * @return array<string, int|string|bool|null> The persistence row data.
	 */
	private function map_campaign_to_update_row( Campaign $campaign ): array {

		$target_amount = $campaign->get_target()->get_amount();

		return [
			'title' => $campaign->get_title(),
			'accepts_donations' => $campaign->accepts_donations(),
			'target_amount' => $target_amount?->get_value(),
			'updated_at' => $this->current_utc_timestamp(),
		];
	}

	/**
	 * Returns current UTC timestamp formatted for storage.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current UTC timestamp.
	 */
	private function current_utc_timestamp(): string {

		return UtcDateTime::now()->format( self::DATETIME_DB_FORMAT );
	}
}
