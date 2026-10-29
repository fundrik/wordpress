<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\CampaignSummary;

use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Infrastructure\CampaignSummary\CampaignSummaryApplicationEventUpdater;
use Fundrik\WordPress\Infrastructure\CampaignSummary\CampaignSummaryException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Stores\CampaignReadModelStore;
use Fundrik\WordPress\Tests\Fixtures\CampaignSummaryDatabaseException;
use Fundrik\WordPress\Tests\Fixtures\CampaignSummaryDatabaseRowNotFoundException;
use Fundrik\WordPress\Tests\Fixtures\FakeDonationRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( CampaignSummaryApplicationEventUpdater::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( DonationId::class )]
#[UsesClass( CampaignReadModelStore::class )]
final class CampaignSummaryApplicationEventUpdaterTest extends MockeryTestCase {

	private const string DONATION_ID = '019b6bcb-2f32-4461-838f-67a1479fbdbe';

	private DonationRepositoryPort&MockInterface $donations;

	private DatabasePort&MockInterface $database;

	private LoggerInterface&MockInterface $logger;

	private CampaignSummaryApplicationEventUpdater $updater;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->donations = Mockery::mock( DonationRepositoryPort::class );
		$this->database = Mockery::mock( DatabasePort::class );
		$this->logger = Mockery::mock( LoggerInterface::class );
		$this->updater = new CampaignSummaryApplicationEventUpdater(
			$this->donations,
			new CampaignReadModelStore( $this->database ),
			$this->logger,
		);
	}

	#[Test]
	public function consume_updates_summary_for_succeeded_donation(): void {

		$this->expect_donation_lookup();

		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->with(
				'fundrik_campaigns',
				77,
				[
					'collected_amount' => 500,
					'donations_count' => 1,
				],
			);

		$this->updater->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_subtracts_summary_for_refunded_donation(): void {

		$this->expect_donation_lookup( status: DonationStatus::Refunded );

		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->with(
				'fundrik_campaigns',
				77,
				[
					'collected_amount' => -500,
					'donations_count' => -1,
				],
			);

		$this->updater->consume( new DonationRefundedEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_ignores_unsupported_donation_event(): void {

		$this->donations->shouldNotReceive( 'find_by_id' );
		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->updater->consume( new DonationCreatedEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_logs_warning_and_throws_for_invalid_donation_id(): void {

		$this->donations->shouldNotReceive( 'find_by_id' );
		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Campaign summary update failed due to invalid donation ID.',
				Mockery::on(
					static fn ( array $context ): bool => ( $context['outcome'] ?? null ) === 'invalid'
						&& ( $context['event_class'] ?? null ) === DonationSucceededEvent::class
						&& ( $context['reason'] ?? null ) === 'donation_id_not_uuid',
				),
			);

		$this->expectException( CampaignSummaryException::class );
		$this->expectExceptionMessage( 'Donation ID must be valid. Given: 11.' );

		$this->updater->consume( new DonationSucceededEvent( EntityId::create( 11 ) ) );
	}

	#[Test]
	public function consume_logs_failure_when_donation_lookup_throws(): void {

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( new FakeDonationRepositoryException( 'Repository failed.' ) );

		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->expect_logged_update_failure();

		$this->updater->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_logs_failure_when_donation_is_missing(): void {

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( null );

		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->expect_logged_update_failure();

		$this->updater->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_logs_failure_when_campaign_id_is_invalid(): void {

		$this->expect_donation_lookup( campaign_id: '123e4567-e89b-42d3-a456-426614174001' );

		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->expect_logged_update_failure();

		$this->updater->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_logs_failure_when_campaign_row_is_missing(): void {

		$this->expect_donation_lookup();
		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->andThrow( new CampaignSummaryDatabaseRowNotFoundException( 'Campaign row not found.' ) );

		$this->expect_logged_update_failure();

		$this->updater->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_logs_failure_when_summary_update_fails(): void {

		$this->expect_donation_lookup();
		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->andThrow( new CampaignSummaryDatabaseException( 'Database failed.' ) );

		$this->expect_logged_update_failure();

		$this->updater->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_logs_refunded_event_name_when_refund_summary_update_fails(): void {

		$this->expect_donation_lookup( status: DonationStatus::Refunded );

		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->andThrow( new CampaignSummaryDatabaseException( 'Database failed.' ) );

		$this->expect_logged_update_failure( event_class: DonationRefundedEvent::class, donation_event: 'refunded' );

		$this->updater->consume( new DonationRefundedEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	/**
	 * Expects a donation lookup for the fixture donation.
	 *
	 * @param int|string $campaign_id Campaign ID fixture.
	 * @param DonationStatus $status Donation status.
	 */
	private function expect_donation_lookup(
		int|string $campaign_id = 77,
		DonationStatus $status = DonationStatus::Succeeded,
	): void {

		$donation = $this->make_succeeded_donation( id: self::DONATION_ID, campaign_id: $campaign_id, amount: 500 );

		if ( $status === DonationStatus::Refunded ) {
			$donation = $donation->refund();
		}

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === self::DONATION_ID,
				),
			)
			->andReturn( $donation );
	}

	/**
	 * Expects a logged campaign summary failure.
	 *
	 * @param string $event_class Donation event class.
	 * @param string $donation_event Donation event name.
	 */
	private function expect_logged_update_failure(
		string $event_class = DonationSucceededEvent::class,
		string $donation_event = 'succeeded',
	): void {

		$this->logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign summary update failed.',
				Mockery::on(
					static fn ( array $context ): bool => ( $context['outcome'] ?? null ) === 'failed'
						&& ( $context['event_class'] ?? null ) === $event_class
						&& ( $context['donation_event'] ?? null ) === $donation_event
						&& ( $context['donation_id'] ?? null ) === self::DONATION_ID
						&& ( $context['exception'] ?? null ) instanceof CampaignSummaryException,
				),
			);
	}
}
