<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Campaigns\Application\Commands\CreateCampaignCommand;
use Fundrik\Core\Components\Campaigns\Application\Commands\SyncCampaignFromSnapshotCommand;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotException;

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
	 * @param CampaignCommandService $campaign_command Provides the public campaign write API.
	 */
	public function __construct(
		private CampaignCommandService $campaign_command,
	) {}

	/**
	 * Synchronizes the saved post snapshot into campaign persistence.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncDataDto $data The normalized synchronization data.
	 * @param bool $creating True when the campaign post is being created.
	 *
	 * @throws CreateCampaignException When campaign creation fails.
	 * @throws SyncCampaignFromSnapshotException When campaign synchronization fails.
	 */
	public function sync( RestCampaignSyncDataDto $data, bool $creating ): void {

		if ( $creating ) {
			$this->campaign_command->create( $this->new_create_command( $data ) );

			return;
		}

		$this->campaign_command->sync_from_snapshot( $this->new_sync_command( $data ) );
	}

	/**
	 * Creates the public command for campaign creation.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncDataDto $data The normalized synchronization data.
	 *
	 * @return CreateCampaignCommand The public creation command.
	 */
	private function new_create_command( RestCampaignSyncDataDto $data ): CreateCampaignCommand {

		return new CreateCampaignCommand(
			id: $data->id,
			title: $data->title,
			accepts_donations: $data->accepts_donations,
			currency_code: $data->target_currency,
			target_amount: $data->target_amount,
		);
	}

	/**
	 * Creates the public command for campaign synchronization.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncDataDto $data The normalized synchronization data.
	 *
	 * @return SyncCampaignFromSnapshotCommand The public synchronization command.
	 */
	private function new_sync_command( RestCampaignSyncDataDto $data ): SyncCampaignFromSnapshotCommand {

		return new SyncCampaignFromSnapshotCommand(
			id: $data->id,
			expected_version: $data->version->get_value(),
			title: $data->title,
			accepts_donations: $data->accepts_donations,
			currency_code: $data->target_currency,
			target_amount: $data->target_amount,
		);
	}
}
