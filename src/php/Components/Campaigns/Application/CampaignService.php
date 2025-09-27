<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\In\CampaignServicePortInterface;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPortInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;

/**
 * Provides application-level operations for managing WordPress campaigns.
 *
 * @since 1.0.0
 */
final readonly class CampaignService implements CampaignServicePortInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignAssembler $assembler Converts between DTOs and domain entities.
	 * @param CampaignRepositoryPortInterface $repository Provides access to campaign storage.
	 * @param CampaignServiceLogger $logger Logs application-level operations and outcomes.
	 */
	public function __construct(
		private CampaignAssembler $assembler,
		private CampaignRepositoryPortInterface $repository,
		private CampaignServiceLogger $logger,
	) {}

	/**
	 * Finds a WordPress-specific campaign by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return Campaign|null The WordPress campaign if found, otherwise null.
	 */
	public function find_campaign_by_id( EntityId $id ): ?Campaign {

		$this->logger->log_find_by_id_started( $id->value );

		try {
			$campaign_dto = $this->repository->find_by_id( $id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_find_by_id_failed_repository( $id->value, $e );
			throw $e;
		}

		if ( $campaign_dto === null ) {

			$this->logger->log_find_by_id_not_found( $id->value );
			return null;
		}

		try {
			$campaign = $this->assembler->from_dto( $campaign_dto );
		} catch ( CampaignAssemblerException $e ) {

			$this->logger->log_find_by_id_failed_assembler( $id->value, $e );
			throw $e;
		}

		$this->logger->log_find_by_id_succeeded( $id->value );

		return $campaign;
	}

	/**
	 * Retrieves all WordPress-specific campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @return array<Campaign> All available WordPress campaign entities.
	 */
	public function find_all_campaigns(): array {

		$this->logger->log_find_all_started();

		try {
			$dto_list = $this->repository->find_all();
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_find_all_failed_repository( $e );
			throw $e;
		}

		try {
			$entities = array_map(
				fn ( CampaignDto $dto ): Campaign => $this->assembler->from_dto( $dto ),
				$dto_list,
			);
		} catch ( CampaignAssemblerException $e ) {

			$this->logger->log_find_all_failed_assembler( $e );
			throw $e;
		}

		$this->logger->log_find_all_succeeded( count( $entities ) );

		return $entities;
	}

	/**
	 * Saves the given WordPress campaign.
	 *
	 * Creates a new campaign or updates an existing one.
	 *
	 * @since 1.0.0
	 *
	 * @param Campaign $campaign The WordPress campaign to save.
	 */
	public function save_campaign( Campaign $campaign ): void {

		$this->logger->log_save_started( $campaign->get_id() );

		try {
			$is_update = $this->repository->exists( $campaign );

			if ( $is_update ) {
				$this->repository->update( $campaign );
			} else {
				$this->repository->insert( $campaign );
			}
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_save_failed_repository( $campaign->get_id(), $e );
			throw $e;
		}

		$this->logger->log_save_succeeded(
			$campaign->get_id(),
			$is_update ? 'update' : 'create',
		);
	}

	/**
	 * Deletes a WordPress campaign by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The ID of the WordPress campaign to delete.
	 */
	public function delete_campaign( EntityId $id ): void {

		$this->logger->log_delete_started( $id->value );

		try {
			$this->repository->delete( $id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_delete_failed_repository( $id->value, $e );
			throw $e;
		}

		$this->logger->log_delete_succeeded( $id->value );
	}
}
