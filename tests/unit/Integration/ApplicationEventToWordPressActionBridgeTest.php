<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration;

use Brain\Monkey\Actions;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsDisabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsEnabledEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignSynchronizedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Integration\ApplicationEventToWordPressActionBridge;
use Fundrik\WordPress\Tests\Fixtures\DummyApplicationEvent;
use Fundrik\WordPress\Tests\Fixtures\DummyCampaignApplicationEvent;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( ApplicationEventToWordPressActionBridge::class )]
final class ApplicationEventToWordPressActionBridgeTest extends WordPressTestCase {

	private LoggerInterface&MockInterface $logger;
	private ApplicationEventToWordPressActionBridge $consumer;

	protected function setUp(): void {

		parent::setUp();

		/** Logger mock.
		 *
		 * @var LoggerInterface&MockInterface $logger
		 */
		$logger = Mockery::mock( LoggerInterface::class );

		$this->logger = $logger;
		$this->consumer = new ApplicationEventToWordPressActionBridge( $this->logger );
	}

	#[Test]
	public function consume_dispatches_campaign_created_event_to_wordpress_actions(): void {

		$event = new CampaignCreatedEvent( EntityId::create( 11 ) );

		Actions\expectDone( 'fundrik_campaign_created' )
			->once()
			->with( 11 );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_campaign_donations_enabled_event_to_wordpress_actions(): void {

		$event = new CampaignDonationsEnabledEvent( EntityId::create( 12 ) );

		Actions\expectDone( 'fundrik_campaign_donations_enabled' )
			->once()
			->with( 12 );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_campaign_donations_disabled_event_to_wordpress_actions(): void {

		$event = new CampaignDonationsDisabledEvent( EntityId::create( 14 ) );

		Actions\expectDone( 'fundrik_campaign_donations_disabled' )
			->once()
			->with( 14 );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_campaign_renamed_event_to_wordpress_actions(): void {

		$event = new CampaignRenamedEvent( EntityId::create( 15 ) );

		Actions\expectDone( 'fundrik_campaign_renamed' )
			->once()
			->with( 15 );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_campaign_target_changed_event_to_wordpress_actions(): void {

		$event = new CampaignTargetChangedEvent( EntityId::create( 16 ) );

		Actions\expectDone( 'fundrik_campaign_target_changed' )
			->once()
			->with( 16 );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_campaign_synchronized_event_to_wordpress_actions(): void {

		$event = new CampaignSynchronizedEvent( EntityId::create( 17 ) );

		Actions\expectDone( 'fundrik_campaign_synchronized' )
			->once()
			->with( 17 );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_campaign_deleted_event_to_wordpress_actions(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 13 ) );

		Actions\expectDone( 'fundrik_campaign_deleted' )
			->once()
			->with( 13 );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_donation_created_event_to_wordpress_actions(): void {

		$event = new DonationCreatedEvent( EntityId::create( '019b6bcb-2f32-4461-838f-67a1479fbdbe' ) );

		Actions\expectDone( 'fundrik_donation_created' )
			->once()
			->with( '019b6bcb-2f32-4461-838f-67a1479fbdbe' );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_donation_succeeded_event_to_wordpress_actions(): void {

		$event = new DonationSucceededEvent( EntityId::create( '019b6bcb-2f32-4461-838f-67a1479fbdbe' ) );

		Actions\expectDone( 'fundrik_donation_succeeded' )
			->once()
			->with( '019b6bcb-2f32-4461-838f-67a1479fbdbe' );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_donation_rejected_event_to_wordpress_actions(): void {

		$event = new DonationRejectedEvent( EntityId::create( '019b6bcb-2f32-4461-838f-67a1479fbdbe' ) );

		Actions\expectDone( 'fundrik_donation_rejected' )
			->once()
			->with( '019b6bcb-2f32-4461-838f-67a1479fbdbe' );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_dispatches_donation_refunded_event_to_wordpress_actions(): void {

		$event = new DonationRefundedEvent( EntityId::create( '019b6bcb-2f32-4461-838f-67a1479fbdbe' ) );

		Actions\expectDone( 'fundrik_donation_refunded' )
			->once()
			->with( '019b6bcb-2f32-4461-838f-67a1479fbdbe' );

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_logs_warning_when_campaign_id_cannot_be_resolved(): void {

		$event = new CampaignCreatedEvent(
			EntityId::create( '2be078f7-7d75-4450-90d0-e7fd204f072e' ),
		);

		/** Warning expectation.
		 *
		 * @var Expectation $logger_warning_expectation
		 */
		$logger_warning_expectation = $this->logger->shouldReceive( 'warning' );

		$logger_warning_expectation
			->once()
			->with(
				'Publishing WordPress action skipped due to invalid campaign ID.',
				Mockery::subset(
					[
						'service_class' => ApplicationEventToWordPressActionBridge::class,
						'logger_class' => ApplicationEventToWordPressActionBridge::class,
						'component' => 'event_bus',
						'layer' => 'integration',
						'system' => 'wordpress',
						'operation' => 'publish',
						'outcome' => 'invalid',
						'event_class' => CampaignCreatedEvent::class,
						'reason' => 'campaign_id_not_int',
					],
				),
			);

		Actions\expectDone( 'fundrik_campaign_created' )->never();
		Actions\expectDone( 'fundrik_campaign_donations_enabled' )->never();
		Actions\expectDone( 'fundrik_campaign_donations_disabled' )->never();
		Actions\expectDone( 'fundrik_campaign_renamed' )->never();
		Actions\expectDone( 'fundrik_campaign_target_changed' )->never();
		Actions\expectDone( 'fundrik_campaign_synchronized' )->never();
		Actions\expectDone( 'fundrik_campaign_deleted' )->never();
		Actions\expectDone( 'fundrik_donation_created' )->never();
		Actions\expectDone( 'fundrik_donation_succeeded' )->never();
		Actions\expectDone( 'fundrik_donation_rejected' )->never();
		Actions\expectDone( 'fundrik_donation_refunded' )->never();

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_logs_warning_when_donation_id_cannot_be_resolved(): void {

		$event = new DonationSucceededEvent( EntityId::create( 11 ) );

		/** Warning expectation.
		 *
		 * @var Expectation $logger_warning_expectation
		 */
		$logger_warning_expectation = $this->logger->shouldReceive( 'warning' );

		$logger_warning_expectation
			->once()
			->with(
				'Publishing WordPress action skipped due to invalid donation ID.',
				Mockery::subset(
					[
						'service_class' => ApplicationEventToWordPressActionBridge::class,
						'logger_class' => ApplicationEventToWordPressActionBridge::class,
						'component' => 'event_bus',
						'layer' => 'integration',
						'system' => 'wordpress',
						'operation' => 'publish',
						'outcome' => 'invalid',
						'event_class' => DonationSucceededEvent::class,
						'reason' => 'donation_id_not_uuid',
					],
				),
			);

		Actions\expectDone( 'fundrik_donation_created' )->never();
		Actions\expectDone( 'fundrik_donation_succeeded' )->never();
		Actions\expectDone( 'fundrik_donation_rejected' )->never();
		Actions\expectDone( 'fundrik_donation_refunded' )->never();

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_ignores_non_campaign_event(): void {

		$event = new DummyApplicationEvent();

		Actions\expectDone( 'fundrik_campaign_created' )->never();
		Actions\expectDone( 'fundrik_campaign_donations_enabled' )->never();
		Actions\expectDone( 'fundrik_campaign_donations_disabled' )->never();
		Actions\expectDone( 'fundrik_campaign_renamed' )->never();
		Actions\expectDone( 'fundrik_campaign_target_changed' )->never();
		Actions\expectDone( 'fundrik_campaign_synchronized' )->never();
		Actions\expectDone( 'fundrik_campaign_deleted' )->never();
		Actions\expectDone( 'fundrik_donation_created' )->never();
		Actions\expectDone( 'fundrik_donation_succeeded' )->never();
		Actions\expectDone( 'fundrik_donation_rejected' )->never();
		Actions\expectDone( 'fundrik_donation_refunded' )->never();

		$this->consumer->consume( $event );
	}

	#[Test]
	public function consume_ignores_unsupported_campaign_event(): void {

		$event = new DummyCampaignApplicationEvent( EntityId::create( 18 ) );

		Actions\expectDone( 'fundrik_campaign_created' )->never();
		Actions\expectDone( 'fundrik_campaign_donations_enabled' )->never();
		Actions\expectDone( 'fundrik_campaign_donations_disabled' )->never();
		Actions\expectDone( 'fundrik_campaign_renamed' )->never();
		Actions\expectDone( 'fundrik_campaign_target_changed' )->never();
		Actions\expectDone( 'fundrik_campaign_synchronized' )->never();
		Actions\expectDone( 'fundrik_campaign_deleted' )->never();
		Actions\expectDone( 'fundrik_donation_created' )->never();
		Actions\expectDone( 'fundrik_donation_succeeded' )->never();
		Actions\expectDone( 'fundrik_donation_rejected' )->never();
		Actions\expectDone( 'fundrik_donation_refunded' )->never();

		$this->consumer->consume( $event );
	}
}
