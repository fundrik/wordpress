<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\WordPress\Components\Campaigns\Application\Events\CampaignSavedEvent;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\In\CampaignServicePortInterface;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPortInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Components\Shared\Application\Ports\Out\EventBusPortInterface;
use Throwable;

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
	 * @param EventBusPortInterface $event_bus Publishes application events to subscribed listeners.
	 */
	public function __construct(
		private CampaignAssembler $assembler,
		private CampaignRepositoryPortInterface $repository,
		private CampaignServiceLogger $logger,
		private EventBusPortInterface $event_bus,
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

		// @infection-ignore-all
		$this->logger->log_find_by_id_started( $id->to_int() );

		try {
			$campaign_dto = $this->repository->find_by_id( $id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_find_by_id_failed_repository( $id->to_int(), $e );
			throw $e;
		}

		if ( $campaign_dto === null ) {
			// @infection-ignore-all
			$this->logger->log_find_by_id_not_found( $id->to_int() );
			return null;
		}

		try {
			$campaign = $this->assembler->from_dto( $campaign_dto );
		} catch ( CampaignAssemblerException $e ) {

			$this->logger->log_find_by_id_failed_assembler( $id->to_int(), $e );
			throw $e;
		}

		// @infection-ignore-all
		$this->logger->log_find_by_id_succeeded( $id->to_int() );

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

		// @infection-ignore-all
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

		// @infection-ignore-all
		$this->logger->log_find_all_succeeded( count( $entities ) );

		return $entities;
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
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

		// @infection-ignore-all
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

		try {
			$this->event_bus->publish(
				new CampaignSavedEvent( $campaign->get_entity_id(), $is_update ),
			);
		} catch ( Throwable $e ) {

			$this->logger->log_publish_saved_event_failed(
				id: $campaign->get_id(),
				e: $e,
			);
		}

		$this->logger->log_save_succeeded(
			$campaign->get_id(),
			$is_update ? 'update' : 'create',
		);
	}
	// phpcs:enable

	/**
	 * Deletes a WordPress campaign by its ID.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $id The ID of the WordPress campaign to delete.
	 */
	public function delete_campaign( EntityId $id ): void {

		// @infection-ignore-all
		$this->logger->log_delete_started( $id->to_int() );

		try {
			$this->repository->delete( $id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_delete_failed_repository( $id->to_int(), $e );
			throw $e;
		}

		try {
			$this->event_bus->publish( new CampaignDeletedEvent( $id ) );
		} catch ( Throwable $e ) {
			$this->logger->log_publish_deleted_event_failed( $id->to_int(), $e );
		}

		$this->logger->log_delete_succeeded( $id->to_int() );
	}
}
