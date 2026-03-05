<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignUseCase;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;

/**
 * Synchronizes the saved campaign post snapshot into Fundrik storage after REST saves.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestAfterInsertCampaignSynchronizer {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignFactory $campaign_factory Builds Campaign entities from primitives.
	 * @param CampaignRepositoryPort $campaign_repository Reads persisted campaigns to resolve
	 *                                                    optimistic-locking versions.
	 * @param SaveCampaignUseCase $save_campaign_use_case Saves campaigns and publishes application events.
	 */
	public function __construct(
		private CampaignFactory $campaign_factory,
		private CampaignRepositoryPort $campaign_repository,
		private SaveCampaignUseCase $save_campaign_use_case,
	) {}

	/**
	 * Synchronizes the saved post snapshot into campaign persistence.
	 *
	 * Uses the persisted campaign version as the expected version for optimistic locking.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncDataDto $data The normalized synchronization data.
	 *
	 * @throws CampaignFactoryException When campaign payload cannot be mapped to the domain model.
	 * @throws CampaignRepositoryExceptionInterface When reading the persisted campaign fails.
	 * @throws SaveCampaignException When saving the campaign fails.
	 */
	public function sync( RestCampaignSyncDataDto $data ): void {

		$expected_version = $this->get_expected_version_or_initial( $data );

		$campaign = $this->campaign_factory->create_from_primitives(
			id: $data->id->get_value(),
			version: $expected_version->get_value(),
			title: $data->title,
			is_active: $data->is_active,
			is_open: $data->is_open,
			has_target: $data->has_target,
			target_amount: $data->target_amount,
			target_currency: $data->target_currency,
		);

		$this->save_campaign_use_case->handle( $campaign );
	}

	/**
	 * Resolves the expected version for optimistic locking.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncDataDto $data The normalized synchronization data.
	 *
	 * @return EntityVersion The expected entity version.
	 *
	 * @throws CampaignRepositoryExceptionInterface When reading the persisted campaign fails.
	 */
	private function get_expected_version_or_initial( RestCampaignSyncDataDto $data ): EntityVersion {

		$persisted = $this->campaign_repository->find_by_id( $data->id );

		return $persisted?->get_version() ?? EntityVersion::initial();
	}
}
