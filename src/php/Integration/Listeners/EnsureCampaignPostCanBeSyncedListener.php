<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Listeners;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventListenerInterface;
use Fundrik\WordPress\Integration\Events\FilterCampaignBeforeSavedViaRestEvent;
use Fundrik\WordPress\Integration\PostTypes\CampaignPostType;
use InvalidArgumentException;
use WP_Error;

/**
 * Ensures that a campaign post is eligible for synchronization with the Campaign domain model.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class EnsureCampaignPostCanBeSyncedListener implements InfrastructureEventListenerInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignFactory $campaign_factory Builds Campaign entities from primitives.
	 * @param CampaignRepositoryPort $campaign_repository Retrieves persisted campaigns for version checks.
	 */
	public function __construct(
		private CampaignFactory $campaign_factory,
		private CampaignRepositoryPort $campaign_repository,
	) {}

	/**
	 * Rejects the REST insert/update when the post cannot be safely synchronized.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterCampaignBeforeSavedViaRestEvent $event Carries the prepared post and request payload.
	 */
	public function handle( FilterCampaignBeforeSavedViaRestEvent $event ): void {

		$data = $this->extract_sync_data_or_reject( $event );

		if ( $data === null ) {
			return;
		}

		if ( ! $this->ensure_domain_accepts_payload_or_reject( $event, $data ) ) {
			return;
		}

		$this->ensure_version_matches_or_reject( $event, $data );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Extracts and normalizes campaign synchronization data from the REST request.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterCampaignBeforeSavedViaRestEvent $event Carries the prepared post and request payload.
	 *
	 * @return array<string, int|string|bool>|null The normalized sync data, or null when the request is rejected.
	 */
	private function extract_sync_data_or_reject( FilterCampaignBeforeSavedViaRestEvent $event, ): ?array {

		$params = $event->request->get_json_params();

		try {

			// phpcs:ignore SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong
			$id = ArrayExtractor::extract_int_optional( $params, 'id' ) ?? TypeCaster::to_int( $event->prepared_post->ID );
			$title = ArrayExtractor::extract_string_optional( $params, 'title' );
			$meta = ArrayExtractor::extract_array_optional( $params, 'meta' ) ?? [];

			// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong
			return [
				'id' => $id,
				'title' => $title,
				'expected_version' => ArrayExtractor::extract_int_optional( $meta, CampaignPostType::ENTITY_VERSION_NAME ),
				'is_open' => ArrayExtractor::extract_bool_optional( $meta, CampaignPostType::META_IS_OPEN ) ?? true,
				'has_target' => ArrayExtractor::extract_bool_optional( $meta, CampaignPostType::META_HAS_TARGET ) ?? false,
				'target_amount' => ArrayExtractor::extract_int_optional( $meta, CampaignPostType::META_TARGET_AMOUNT ) ?? 0,
			];
			// phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong

		} catch ( ArrayExtractionException | InvalidArgumentException $e ) {

			$event->reject(
				new WP_Error(
					'fundrik_campaign_invalid_payload',
					$e->getMessage(),
					[ 'status' => 422 ],
				),
			);

			return null;
		}
	}

	/**
	 * Ensures that the extracted payload satisfies Campaign domain invariants.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterCampaignBeforeSavedViaRestEvent $event Carries the prepared post and request payload.
	 * @param array<string, int|string|bool> $data The normalized synchronization data.
	 *
	 * @return bool True when the payload is accepted by the domain, false when rejected.
	 */
	private function ensure_domain_accepts_payload_or_reject(
		FilterCampaignBeforeSavedViaRestEvent $event,
		array $data,
	): bool {

		try {

			$this->campaign_factory->create(
				id: $data['id'],
				version: EntityVersion::initial(),
				title: $data['title'] ?? 'test',
				is_active: true,
				is_open: $data['is_open'],
				has_target: $data['has_target'],
				target_amount: $data['target_amount'],
			);

			return true;

		} catch ( CampaignFactoryException $e ) {

			$event->reject(
				new WP_Error(
					'fundrik_campaign_validation_failed',
					$e->getMessage(),
					[ 'status' => 422 ],
				),
			);

			return false;
		}
	}

	/**
	 * Ensures that the editor state matches the latest persisted Campaign version.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterCampaignBeforeSavedViaRestEvent $event Carries the prepared post and request payload.
	 * @param array<string, int|string|bool> $data The normalized synchronization data.
	 */
	private function ensure_version_matches_or_reject( FilterCampaignBeforeSavedViaRestEvent $event, array $data ): void {

		if ( $data['expected_version'] === null ) {
			return;
		}

		try {
			$persisted = $this->campaign_repository->find_by_id( EntityId::create( $data['id'] ) );
		} catch ( CampaignRepositoryExceptionInterface | InvalidEntityIdException $e ) {

			$event->reject(
				new WP_Error(
					'fundrik_campaign_version_check_failed',
					$e->getMessage(),
					[ 'status' => 500 ],
				),
			);

			return;
		}

		if ( $persisted === null ) {

			$event->reject(
				new WP_Error(
					'fundrik_campaign_not_found',
					'Campaign no longer exists or is out of date. Refresh the page and try again.',
					[ 'status' => 409 ],
				),
			);

			return;
		}

		$current_version = $persisted->get_version()->get_value();

		if ( $current_version === $data['expected_version'] ) {
			return;
		}

		$event->reject(
			new WP_Error(
				'fundrik_campaign_version_mismatch',
				sprintf(
					// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
					'Campaign data is out of date. Refresh the page and try again. Expected version %d, current version %d.',
					// phpcs:enable SlevomatCodingStandard.Files.LineLength.LineTooLong
					$data['expected_version'],
					$current_version,
				),
				[
					'status' => 409,
					'expected_version' => $data['expected_version'],
					'current_version' => $current_version,
				],
			),
		);
	}
	// phpcs:enable
}
