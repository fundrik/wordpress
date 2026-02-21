<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
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
	 * @param CampaignRepositoryPort $campaign_repository Saves campaigns to persistence.
	 */
	public function __construct(
		private CampaignFactory $campaign_factory,
		private CampaignRepositoryPort $campaign_repository,
	) {}

	/**
	 * Synchronizes the saved post snapshot into campaign persistence.
	 *
	 * Uses the persisted campaign version as the expected version for optimistic locking.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncDataDto $data The normalized synchronization data.
	 */
	public function sync( RestCampaignSyncDataDto $data ): void {

		$expected_version = $this->get_expected_version_or_initial( $data );

		try {

			$campaign = $this->campaign_factory->create(
				id: $data->id,
				version: $expected_version,
				title: $data->title,
				is_active: true,
				is_open: $data->is_open,
				has_target: $data->has_target,
				target_amount: $data->target_amount,
			);

			$this->campaign_repository->save( $campaign );

		} catch ( CampaignFactoryException | CampaignRepositoryExceptionInterface ) {
			return;
		}
	}

	/**
	 * Resolves the expected version for optimistic locking.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncDataDto $data The normalized synchronization data.
	 *
	 * @return EntityVersion The expected entity version.
	 */
	private function get_expected_version_or_initial( RestCampaignSyncDataDto $data ): EntityVersion {

		try {
			$persisted = $this->campaign_repository->find_by_id( $data->id );
		} catch ( CampaignRepositoryExceptionInterface ) {
			return EntityVersion::initial();
		}

		return $persisted?->get_version() ?? EntityVersion::initial();
	}
}
