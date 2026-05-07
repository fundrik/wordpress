<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\CampaignSummary;

use Fundrik\Core\Components\Donations\Application\Events\DonationApplicationEventInterface;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventConsumerInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseRowNotFoundExceptionInterface;
use Fundrik\WordPress\Infrastructure\Stores\CampaignReadModelStore;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Updates campaign summary fields from supported donation application events.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CampaignSummaryApplicationEventUpdater implements ApplicationEventConsumerInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationRepositoryPort $donations Retrieves persisted donations for summary updates.
	 * @param CampaignReadModelStore $read_model_store Writes campaign read model values.
	 * @param LoggerInterface $logger Writes structured log entries for event-driven summary updates.
	 */
	public function __construct(
		private DonationRepositoryPort $donations,
		private CampaignReadModelStore $read_model_store,
		private LoggerInterface $logger,
	) {}

	/**
	 * Consumes supported donation events by updating campaign summary fields.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 */
	#[Override]
	public function consume( ApplicationEventInterface $event ): void {

		match ( true ) {
			$event instanceof DonationSucceededEvent => $this->update_succeeded_donation_summary( $event ),
			$event instanceof DonationRefundedEvent => $this->update_refunded_donation_summary( $event ),
			default => null,
		};
	}

	/**
	 * Updates campaign summary fields for a succeeded donation event.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationSucceededEvent $event Donation succeeded event.
	 */
	private function update_succeeded_donation_summary( DonationSucceededEvent $event ): void {

		$donation_id = $this->resolve_donation_id( $event );

		try {
			$this->apply_summary_delta( $donation_id, 1 );
		} catch ( Throwable $e ) {
			$this->log_summary_update_failure( $event, $donation_id, $e );

			throw $e;
		}
	}

	/**
	 * Updates campaign summary fields for a refunded donation event.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationRefundedEvent $event Donation refunded event.
	 */
	private function update_refunded_donation_summary( DonationRefundedEvent $event ): void {

		$donation_id = $this->resolve_donation_id( $event );

		try {
			$this->apply_summary_delta( $donation_id, -1 );
		} catch ( Throwable $e ) {
			$this->log_summary_update_failure( $event, $donation_id, $e );

			throw $e;
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Applies the given campaign summary delta for the donation.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationId $donation_id Donation ID.
	 * @param int $summary_delta_sign Summary delta sign.
	 *
	 * @throws CampaignSummaryException When the update fails.
	 */
	private function apply_summary_delta( DonationId $donation_id, int $summary_delta_sign ): void {

		$donation = $this->require_donation( $donation_id );

		$campaign_id = $this->require_campaign_id( $donation, $donation_id );
		$amount_delta = $donation->get_money()->get_amount()->get_value() * $summary_delta_sign;
		$count_delta = $summary_delta_sign;

		try {
			$this->read_model_store->apply_summary_deltas( $campaign_id, $amount_delta, $count_delta );
		} catch ( DatabaseRowNotFoundExceptionInterface $e ) {
			throw new CampaignSummaryException(
				sprintf(
					'Cannot update campaign summary for donation "%s": campaign "%d" not found.',
					$donation_id->get_value(),
					$campaign_id,
				),
				previous: $e,
			);
		} catch ( DatabaseExceptionInterface $e ) {
			throw new CampaignSummaryException(
				sprintf( 'Failed to update campaign summary for campaign "%d".', $campaign_id ),
				previous: $e,
			);
		}
	}
	// phpcs:enable

	/**
	 * Resolves the donation ID from the given event.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationApplicationEventInterface $event Donation application event.
	 *
	 * @return DonationId Donation ID.
	 *
	 * @throws CampaignSummaryException When the donation ID is invalid.
	 */
	private function resolve_donation_id( DonationApplicationEventInterface $event ): DonationId {

		try {
			return DonationId::from_entity_id( $event->get_donation_id() );
		} catch ( InvalidDonationIdException ) {
			$this->log_invalid_donation_id( $event );

			throw new CampaignSummaryException(
				sprintf(
					'Donation ID must be valid. Given: %s.',
					$event->get_donation_id()->get_value(),
				),
			);
		}
	}

	/**
	 * Logs an invalid donation ID.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationApplicationEventInterface $event Donation application event.
	 */
	private function log_invalid_donation_id( DonationApplicationEventInterface $event ): void {

		$this->logger->warning(
			'Campaign summary update failed due to invalid donation ID.',
			$this->build_logger_context(
				[
					'operation' => 'update',
					'outcome' => 'invalid',
					'event_class' => $event::class,
					'reason' => 'donation_id_not_uuid',
				],
			),
		);
	}

	/**
	 * Retrieves the persisted donation required for the summary update.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationId $donation_id Donation ID.
	 *
	 * @return Donation Persisted donation.
	 *
	 * @throws CampaignSummaryException When the donation cannot be loaded.
	 */
	private function require_donation( DonationId $donation_id ): Donation {

		try {
			$donation = $this->donations->find_by_id( $donation_id->to_entity_id() );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new CampaignSummaryException(
				sprintf( 'Failed to retrieve donation "%s".', $donation_id->get_value() ),
				previous: $e,
			);
		}

		if ( $donation === null ) {
			throw new CampaignSummaryException(
				sprintf(
					'Cannot update campaign summary for donation "%s": donation not found.',
					$donation_id->get_value(),
				),
			);
		}

		return $donation;
	}

	/**
	 * Returns the campaign ID associated with the donation.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation Persisted donation.
	 * @param DonationId $donation_id Donation ID for exception messages.
	 *
	 * @return int Campaign ID.
	 *
	 * @throws CampaignSummaryException When the campaign ID is invalid.
	 */
	private function require_campaign_id( Donation $donation, DonationId $donation_id ): int {

		try {
			return CampaignId::from_entity_id(
				$donation->get_campaign_id(),
			)->get_value();
		} catch ( InvalidCampaignIdException $e ) {
			throw new CampaignSummaryException(
				sprintf(
					'Cannot update campaign summary for donation "%s": campaign ID is invalid.',
					$donation_id->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Logs a failed campaign summary update attempt.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationApplicationEventInterface $event Donation application event.
	 * @param DonationId $donation_id Donation ID.
	 * @param Throwable $exception Summary update failure.
	 */
	private function log_summary_update_failure(
		DonationApplicationEventInterface $event,
		DonationId $donation_id,
		Throwable $exception,
	): void {

		$donation_event_name = match ( true ) {
			$event instanceof DonationSucceededEvent => 'succeeded',
			$event instanceof DonationRefundedEvent => 'refunded',
			default => 'unknown',
		};

		$this->logger->error(
			'Campaign summary update failed.',
			$this->build_logger_context(
				[
					'operation' => 'update',
					'outcome' => 'failed',
					'event_class' => $event::class,
					'donation_event' => $donation_event_name,
					'donation_id' => $donation_id->get_value(),
					'exception' => $exception,
				],
			),
		);
	}

	/**
	 * Builds structured logger context for event-driven campaign summary updates.
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
			'component' => 'campaign_summary',
			'layer' => 'infrastructure',
			'system' => 'wordpress',
		] + $extra;
	}
}
