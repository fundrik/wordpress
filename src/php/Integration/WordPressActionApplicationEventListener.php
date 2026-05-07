<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignApplicationEventInterface;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsDisabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsEnabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignSynchronizedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationApplicationEventInterface;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventListenerInterface;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Publishes WordPress actions for supported application events.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WordPressActionApplicationEventListener implements ApplicationEventListenerInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for application event handling.
	 */
	public function __construct(
		private LoggerInterface $logger,
	) {}

	/**
	 * Handles the given event by publishing matching WordPress actions.
	 *
	 * Unknown events are ignored.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 */
	#[Override]
	public function handle( ApplicationEventInterface $event ): void {

		match ( true ) {
			$event instanceof CampaignApplicationEventInterface => $this->handle_campaign_event( $event ),
			$event instanceof DonationApplicationEventInterface => $this->handle_donation_event( $event ),
			default => null,
		};
	}

	/**
	 * Handles the given campaign event by publishing WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignApplicationEventInterface $event Campaign application event.
	 */
	private function handle_campaign_event( CampaignApplicationEventInterface $event ): void {

		$publish_action = match ( true ) {
			$event instanceof CampaignCreatedEvent => $this->publish_campaign_created( ... ),
			$event instanceof CampaignDonationsEnabledEvent => $this->publish_campaign_donations_enabled( ... ),
			$event instanceof CampaignDonationsDisabledEvent => $this->publish_campaign_donations_disabled( ... ),
			$event instanceof CampaignRenamedEvent => $this->publish_campaign_renamed( ... ),
			$event instanceof CampaignTargetChangedEvent => $this->publish_campaign_target_changed( ... ),
			$event instanceof CampaignSynchronizedEvent => $this->publish_campaign_synchronized( ... ),
			$event instanceof CampaignDeletedEvent => $this->publish_campaign_deleted( ... ),
			default => null,
		};

		if ( $publish_action === null ) {
			return;
		}

		try {
			$campaign_id = CampaignId::from_entity_id( $event->get_campaign_id() )->get_value();
		} catch ( InvalidCampaignIdException ) {
			$this->log_invalid_campaign_id( $event, 'campaign_id_not_int' );
			return;
		}

		$publish_action( $campaign_id );
	}

	/**
	 * Handles the given donation event by publishing WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationApplicationEventInterface $event Donation application event.
	 */
	private function handle_donation_event( DonationApplicationEventInterface $event ): void {

		$publish_action = match ( true ) {
			$event instanceof DonationCreatedEvent => $this->publish_donation_created( ... ),
			$event instanceof DonationSucceededEvent => $this->publish_donation_succeeded( ... ),
			$event instanceof DonationRejectedEvent => $this->publish_donation_rejected( ... ),
			$event instanceof DonationRefundedEvent => $this->publish_donation_refunded( ... ),
			default => null,
		};

		if ( $publish_action === null ) {
			return;
		}

		try {
			$donation_id = DonationId::from_entity_id( $event->get_donation_id() )->get_value();
		} catch ( InvalidDonationIdException ) {
			$this->log_invalid_donation_id( $event, 'donation_id_not_uuid' );
			return;
		}

		$publish_action( $donation_id );
	}

	/**
	 * Publishes a campaign-created event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function publish_campaign_created( int $campaign_id ): void {

		/**
		 * Fires after a campaign has been created.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id Campaign ID.
		 */
		do_action( 'fundrik_campaign_created', $campaign_id );
	}

	/**
	 * Publishes a campaign-donations-enabled event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function publish_campaign_donations_enabled( int $campaign_id ): void {

		/**
		 * Fires after campaign donations have been enabled.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id Campaign ID.
		 */
		do_action( 'fundrik_campaign_donations_enabled', $campaign_id );
	}

	/**
	 * Publishes a campaign-donations-disabled event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function publish_campaign_donations_disabled( int $campaign_id ): void {

		/**
		 * Fires after campaign donations have been disabled.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id Campaign ID.
		 */
		do_action( 'fundrik_campaign_donations_disabled', $campaign_id );
	}

	/**
	 * Publishes a campaign-renamed event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function publish_campaign_renamed( int $campaign_id ): void {

		/**
		 * Fires after a campaign has been renamed.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id Campaign ID.
		 */
		do_action( 'fundrik_campaign_renamed', $campaign_id );
	}

	/**
	 * Publishes a campaign-target-changed event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function publish_campaign_target_changed( int $campaign_id ): void {

		/**
		 * Fires after a campaign target has changed.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id Campaign ID.
		 */
		do_action( 'fundrik_campaign_target_changed', $campaign_id );
	}

	/**
	 * Publishes a campaign-synchronized event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function publish_campaign_synchronized( int $campaign_id ): void {

		/**
		 * Fires after a campaign has been synchronized.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id Campaign ID.
		 */
		do_action( 'fundrik_campaign_synchronized', $campaign_id );
	}

	/**
	 * Publishes a campaign-deleted event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 */
	private function publish_campaign_deleted( int $campaign_id ): void {

		/**
		 * Fires after a campaign has been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id Campaign ID.
		 */
		do_action( 'fundrik_campaign_deleted', $campaign_id );
	}

	/**
	 * Publishes a donation-created event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $donation_id Donation ID.
	 */
	private function publish_donation_created( string $donation_id ): void {

		/**
		 * Fires after a donation has been created.
		 *
		 * @since 1.0.0
		 *
		 * @param string $donation_id Donation ID.
		 */
		do_action( 'fundrik_donation_created', $donation_id );
	}

	/**
	 * Publishes a donation-succeeded event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $donation_id Donation ID.
	 */
	private function publish_donation_succeeded( string $donation_id ): void {

		/**
		 * Fires after a donation has succeeded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $donation_id Donation ID.
		 */
		do_action( 'fundrik_donation_succeeded', $donation_id );
	}

	/**
	 * Publishes a donation-rejected event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $donation_id Donation ID.
	 */
	private function publish_donation_rejected( string $donation_id ): void {

		/**
		 * Fires after a donation has been rejected.
		 *
		 * @since 1.0.0
		 *
		 * @param string $donation_id Donation ID.
		 */
		do_action( 'fundrik_donation_rejected', $donation_id );
	}

	/**
	 * Publishes a donation-refunded event to WordPress actions.
	 *
	 * @since 1.0.0
	 *
	 * @param string $donation_id Donation ID.
	 */
	private function publish_donation_refunded( string $donation_id ): void {

		/**
		 * Fires after a donation has been refunded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $donation_id Donation ID.
		 */
		do_action( 'fundrik_donation_refunded', $donation_id );
	}

	/**
	 * Logs that campaign ID resolution has failed for a given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 * @param string $reason Machine-readable reason for resolution failure.
	 */
	private function log_invalid_campaign_id( ApplicationEventInterface $event, string $reason ): void {

		$this->logger->warning(
			'Publishing WordPress action skipped due to invalid campaign ID.',
			$this->build_logger_context(
				[
					'operation' => 'publish',
					'outcome' => 'invalid',
					'event_class' => $event::class,
					'reason' => $reason,
				],
			),
		);
	}

	/**
	 * Logs that donation ID resolution has failed for a given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 * @param string $reason Machine-readable reason for resolution failure.
	 */
	private function log_invalid_donation_id( ApplicationEventInterface $event, string $reason ): void {

		$this->logger->warning(
			'Publishing WordPress action skipped due to invalid donation ID.',
			$this->build_logger_context(
				[
					'operation' => 'publish',
					'outcome' => 'invalid',
					'event_class' => $event::class,
					'reason' => $reason,
				],
			),
		);
	}

	/**
	 * Builds structured logger context for handling application events.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $extra Additional context entries.
	 *
	 * @return array<string,mixed> Structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function build_logger_context( array $extra = [] ): array {

		return [
			'service_class' => self::class,
			'logger_class' => self::class,
			'component' => 'event_bus',
			'layer' => 'integration',
			'system' => 'wordpress',
		] + $extra;
	}
}
