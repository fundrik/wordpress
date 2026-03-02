<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventPublisherPort;

/**
 * Publishes known application events to WordPress actions.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WordPressActionApplicationEventPublisher implements ApplicationEventPublisherPort {

	/**
	 * Publishes the given event to matching WordPress actions.
	 *
	 * Unknown events are ignored.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event The application event.
	 */
	public function publish( ApplicationEventInterface $event ): void {

		$campaign_id = $this->get_campaign_id_or_null( $event );

		if ( $campaign_id === null ) {
			return;
		}

		if ( $event instanceof CampaignCreatedEvent ) {
			$this->publish_campaign_created( $campaign_id );
			return;
		}

		if ( $event instanceof CampaignUpdatedEvent ) {
			$this->publish_campaign_updated( $campaign_id );
			return;
		}

		if ( ! $event instanceof CampaignDeletedEvent ) {
			return;
		}

		$this->publish_campaign_deleted( $campaign_id );
	}

	/**
	 * Publishes a campaign-created event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 */
	private function publish_campaign_created( int $campaign_id ): void {

		/**
		 * Fires after a campaign has been created.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id The campaign ID.
		 */
		do_action( 'fundrik_campaign_created', $campaign_id );

		/**
		 * Fires after a campaign has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id The campaign ID.
		 */
		do_action( 'fundrik_campaign_saved', $campaign_id );
	}

	/**
	 * Publishes a campaign-updated event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 */
	private function publish_campaign_updated( int $campaign_id ): void {

		/**
		 * Fires after a campaign has been updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id The campaign ID.
		 */
		do_action( 'fundrik_campaign_updated', $campaign_id );

		/**
		 * Fires after a campaign has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id The campaign ID.
		 */
		do_action( 'fundrik_campaign_saved', $campaign_id );
	}

	/**
	 * Publishes a campaign-deleted event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 */
	private function publish_campaign_deleted( int $campaign_id ): void {

		/**
		 * Fires after a campaign has been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id The campaign ID.
		 */
		do_action( 'fundrik_campaign_deleted', $campaign_id );
	}

	/**
	 * Resolves campaign ID from an event object.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event The application event.
	 *
	 * @return int|null The campaign ID when available and int-compatible, otherwise null.
	 */
	private function get_campaign_id_or_null( ApplicationEventInterface $event ): ?int {

		if ( ! property_exists( $event, 'campaign_id' ) ) {
			return null;
		}

		$campaign_id = $event->campaign_id;

		if ( ! $campaign_id instanceof EntityId ) {
			return null;
		}

		$value = $campaign_id->get_value();

		if ( ! is_int( $value ) ) {
			return null;
		}

		return $value;
	}
}
