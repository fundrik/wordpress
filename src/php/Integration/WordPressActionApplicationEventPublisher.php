<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignApplicationEventInterface;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventPublisherPort;
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
	 * @param ApplicationEventInterface $event The application event.
	 */
	public function publish( ApplicationEventInterface $event ): void {

		if ( ! $event instanceof CampaignApplicationEventInterface ) {
			return;
		}

		$publisher = match ( true ) {
			$event instanceof CampaignCreatedEvent => $this->publish_campaign_created( ... ),
			$event instanceof CampaignUpdatedEvent => $this->publish_campaign_updated( ... ),
			$event instanceof CampaignDeletedEvent => $this->publish_campaign_deleted( ... ),
			default => null,
		};

		if ( $publisher === null ) {
			return;
		}

		try {
			$campaign_id = $event->get_campaign_id()->get_as_int();
		} catch ( InvalidEntityIdException ) {
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
	 * Logs that campaign ID resolution has failed for a given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event The application event.
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
	 * @return array<string,mixed> The structured context payload.
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
