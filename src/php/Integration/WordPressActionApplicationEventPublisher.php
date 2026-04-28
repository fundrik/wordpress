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
use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventPublisherPort;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Publishes known application events to WordPress actions.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WordPressActionApplicationEventPublisher implements ApplicationEventPublisherPort {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for application event publishing.
	 */
	public function __construct(
		private LoggerInterface $logger,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Publishes the given event to matching WordPress actions.
	 *
	 * Unknown events are ignored.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 */
	#[Override]
	public function publish( ApplicationEventInterface $event ): void {

		if ( ! $event instanceof CampaignApplicationEventInterface ) {
			return;
		}

		$publisher = match ( true ) {
			$event instanceof CampaignCreatedEvent => $this->publish_campaign_created( ... ),
			$event instanceof CampaignDonationsEnabledEvent => $this->publish_campaign_donations_enabled( ... ),
			$event instanceof CampaignDonationsDisabledEvent => $this->publish_campaign_donations_disabled( ... ),
			$event instanceof CampaignRenamedEvent => $this->publish_campaign_renamed( ... ),
			$event instanceof CampaignTargetChangedEvent => $this->publish_campaign_target_changed( ... ),
			$event instanceof CampaignSynchronizedEvent => $this->publish_campaign_synchronized( ... ),
			$event instanceof CampaignDeletedEvent => $this->publish_campaign_deleted( ... ),
			default => null,
		};

		if ( $publisher === null ) {
			return;
		}

		try {
			$campaign_id = CampaignId::from_entity_id( $event->get_campaign_id() )->get_value();
		} catch ( InvalidCampaignIdException ) {
			$this->log_invalid_campaign_id( $event, 'campaign_id_not_int' );
			return;
		}

		$publisher( $campaign_id );
	}
	// phpcs:enable

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
	 * Logs that campaign ID resolution has failed for a given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 * @param string $reason Machine-readable reason for resolution failure.
	 */
	private function log_invalid_campaign_id( ApplicationEventInterface $event, string $reason ): void {

		$this->logger->warning(
			'Publishing application event skipped due to invalid campaign ID.',
			$this->logger_context(
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
	 * Builds structured logger context for publishing application events.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $extra Additional context entries.
	 *
	 * @return array<string,mixed> Structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( array $extra = [] ): array {

		return [
			'service_class' => self::class,
			'logger_class' => self::class,
			'component' => 'event_bus',
			'layer' => 'integration',
			'system' => 'wordpress',
		] + $extra;
	}
}
