<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDto;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDtoFactory;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignDtoFactoryException;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPort;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Infrastructure\Database\DatabaseException;
use Fundrik\WordPress\Infrastructure\Database\DatabaseInterface;

/**
 * Persists campaigns and maps rows to DTOs.
 *
 * @since 1.0.0
 */
final readonly class CampaignRepository implements CampaignRepositoryPort {

	private const TABLE_NAME = 'fundrik_campaigns';

	public function __construct(
		private DatabaseInterface $db,
		private CampaignDtoFactory $dto_factory,
	) {}

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
				sprintf( 'Cannot map campaign row to DTO. Given: ID %d.', $id->get_as_int() ),
				previous: $e,
			);
		}
	}

	public function find_all(): array {

		try {
			$rows = $this->db->get_all( self::TABLE_NAME );
		} catch ( DatabaseException $e ) {
			throw new CampaignRepositoryException( 'Cannot fetch campaigns: persistence error.', previous: $e );
		}

		try {
			return array_map(
				fn ( array $row ) => $this->dto_factory->from_array( $row ),
				$rows,
			);
		} catch ( CampaignDtoFactoryException $e ) {
			throw new CampaignRepositoryException( 'Cannot map campaign rows to DTOs.', previous: $e );
		}
	}

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
