<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Listeners;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Toolbox\ArrayExtractionException;
use Fundrik\Toolbox\ArrayExtractor;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventListenerInterface;
use Fundrik\WordPress\Integration\Events\ActionCampaignSavedViaRestEvent;
use Fundrik\WordPress\Integration\Helpers\Meta;
use Fundrik\WordPress\Integration\PostTypes\CampaignPostType;
use InvalidArgumentException;

/**
 * Synchronizes the saved campaign post state into the Fundrik campaigns storage.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class SyncCampaignAfterRestSaveListener implements InfrastructureEventListenerInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignFactory $campaign_factory Builds Campaign entities from primitives.
	 * @param CampaignRepositoryPort $campaign_repository Saves campaigns in Fundrik storage.
	 */
	public function __construct(
		private CampaignFactory $campaign_factory,
		private CampaignRepositoryPort $campaign_repository,
	) {}

	/**
	 * Saves the campaign snapshot into Fundrik storage and updates the stored version in post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param ActionCampaignSavedViaRestEvent $event Carries the saved post and the REST request context.
	 */
	public function handle( ActionCampaignSavedViaRestEvent $event ): void {

		$data = $this->extract_sync_data_or_null( $event );

		if ( $data === null ) {
			return;
		}

		$data['expected_version'] ??= EntityVersion::initial();

		try {

			$campaign = $this->campaign_factory->create(
				id: $data['id'],
				version: $data['expected_version'],
				title: $data['title'],
				is_active: true,
				is_open: $data['is_open'],
				has_target: $data['has_target'],
				target_amount: $data['target_amount'],
			);

			$this->campaign_repository->save( $campaign );
		} catch ( CampaignFactoryException | CampaignRepositoryExceptionInterface ) {
			return;
		}
	}

	/**
	 * Extracts and normalizes synchronization data from the saved post and request.
	 *
	 * @since 1.0.0
	 *
	 * @param ActionCampaignSavedViaRestEvent $event Carries the saved post and the REST request context.
	 *
	 * @return array<string, int|string|bool>|null The normalized data or null when the payload is not usable.
	 */
	private function extract_sync_data_or_null( ActionCampaignSavedViaRestEvent $event ): ?array {

		$params = $event->request->get_json_params();

		try {

			$id = TypeCaster::to_int( $event->post->ID );
			$title = TypeCaster::to_string( $event->post->post_title );

			$is_open = Meta::get_post_meta_or_null( $id, CampaignPostType::META_IS_OPEN );
			$has_target = Meta::get_post_meta_or_null( $id, CampaignPostType::META_HAS_TARGET );
			$target_amount = Meta::get_post_meta_or_null( $id, CampaignPostType::META_TARGET_AMOUNT );

			$params_meta = ArrayExtractor::extract_array_optional( $params, 'meta' ) ?? [];

			// phpcs:disable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong
			return [
				'id' => $id,
				'title' => $title,
				'expected_version' => ArrayExtractor::extract_int_optional( $params_meta, CampaignPostType::ENTITY_VERSION_NAME ),
				'is_open' => TypeCaster::to_bool( Meta::normalize_wp_bool_value( $is_open ) ),
				'has_target' => TypeCaster::to_bool( Meta::normalize_wp_bool_value( $has_target ) ),
				'target_amount' => TypeCaster::to_int( $target_amount ),
			];
			// phpcs:enable SlevomatCodingStandard.Functions.RequireMultiLineCall.RequiredMultiLineCall, SlevomatCodingStandard.Files.LineLength.LineTooLong

		} catch ( ArrayExtractionException | InvalidArgumentException ) {

			return null;
		}
	}
}
