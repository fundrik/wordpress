<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Listeners;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventListenerInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterCampaignRestResponseEvent;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\CampaignPostType;

/**
 * Attaches the current campaign version to the REST response payload.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ProvideCampaignVersionForSyncListener implements InfrastructureEventListenerInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignRepositoryPort $campaign_repository Retrieves campaigns for response enrichment.
	 */
	public function __construct(
		private CampaignRepositoryPort $campaign_repository,
	) {}

	/**
	 * Adds the campaign version into the response meta when the campaign exists in Fundrik storage.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterCampaignRestResponseEvent $event Carries the response, post, and request context.
	 */
	public function handle( FilterCampaignRestResponseEvent $event ): void {

		$entity_id = EntityId::create( TypeCaster::to_int( $event->post->ID ) );

		try {
			$campaign = $this->campaign_repository->find_by_id( $entity_id );
		} catch ( CampaignRepositoryExceptionInterface ) {
			return;
		}

		if ( $campaign === null ) {
			return;
		}

		$meta = $event->response->data['meta'] ?? [];

		if ( ! is_array( $meta ) ) {
			$meta = [];
		}

		$meta[ CampaignPostType::ENTITY_VERSION_NAME ] = $campaign->get_version()->get_value();

		$event->response->data['meta'] = $meta;
	}
}
