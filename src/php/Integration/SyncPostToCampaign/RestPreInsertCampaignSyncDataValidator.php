<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use WP_Error;

/**
 * Validates the campaign synchronization data before REST saves.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestPreInsertCampaignSyncDataValidator {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignFactory $campaign_factory Builds Campaign entities from primitives.
	 * @param CampaignRepositoryPort $campaign_repository Retrieves campaigns for version checks.
	 */
	public function __construct(
		private CampaignFactory $campaign_factory,
		private CampaignRepositoryPort $campaign_repository,
	) {}

	/**
	 * Validates the synchronization data.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncData $data The synchronization data.
	 *
	 * @return WP_Error|null A WP_Error when rejected, or null when accepted.
	 */
	public function validate_or_error( RestCampaignSyncData $data ): ?WP_Error {

		$target_error = $this->validate_target_or_error( $data );

		if ( $target_error instanceof WP_Error ) {
			return $target_error;
		}

		$domain_error = $this->validate_domain_or_error( $data );

		if ( $domain_error instanceof WP_Error ) {
			return $domain_error;
		}

		return $this->validate_version_or_error( $data );
	}

	/**
	 * Validates the target amount constraint.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncData $data The synchronization data.
	 *
	 * @return WP_Error|null A WP_Error when rejected, or null when accepted.
	 */
	private function validate_target_or_error( RestCampaignSyncData $data ): ?WP_Error {

		if ( ! $data->has_target || ( $data->target_amount !== null && $data->target_amount > 0 ) ) {
			return null;
		}

		return new WP_Error(
			'fundrik_campaign_validation_failed',
			sprintf(
				'Target amount must be positive when targeting is enabled. Given: %s.',
				$data->target_amount === null ? 'null' : (string) $data->target_amount,
			),
			[ 'status' => 422 ],
		);
	}

	/**
	 * Validates the payload against Campaign domain invariants.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncData $data The synchronization data.
	 *
	 * @return WP_Error|null A WP_Error when rejected, or null when accepted.
	 */
	private function validate_domain_or_error( RestCampaignSyncData $data ): ?WP_Error {

		try {

			$this->campaign_factory->create_from_primitives(
				id: $data->id->get_value(),
				version: $data->version->get_value(),
				title: $data->title,
				accepts_donations: $data->accepts_donations,
				currency_code: $data->target_currency,
				target_amount: $data->target_amount,
			);

			return null;

		} catch ( CampaignFactoryException $e ) {

			return new WP_Error(
				'fundrik_campaign_validation_failed',
				$e->getMessage(),
				[ 'status' => 422 ],
			);
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Ensures that the expected version matches the persisted Campaign version.
	 *
	 * @since 1.0.0
	 *
	 * @param RestCampaignSyncData $data The synchronization data.
	 *
	 * @return WP_Error|null A WP_Error when rejected, or null when accepted.
	 */
	private function validate_version_or_error( RestCampaignSyncData $data ): ?WP_Error {

		try {
			$persisted = $this->campaign_repository->find_by_id( $data->id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			return new WP_Error(
				'fundrik_campaign_version_check_failed',
				$e->getMessage(),
				[ 'status' => 500 ],
			);
		}

		$current_version = $persisted === null ? EntityVersion::initial() : $persisted->get_version();

		if ( $current_version->equals( $data->version ) ) {
			return null;
		}

		return new WP_Error(
			'fundrik_campaign_version_mismatch',
			sprintf(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'Campaign data is out of date. Refresh the page and try again. Expected version %d, current version %d.',
				$data->version->get_value(),
				$current_version->get_value(),
			),
			[
				'status' => 409,
				'expected_version' => $data->version->get_value(),
				'current_version' => $current_version->get_value(),
			],
		);
	}
	// phpcs:enable
}
