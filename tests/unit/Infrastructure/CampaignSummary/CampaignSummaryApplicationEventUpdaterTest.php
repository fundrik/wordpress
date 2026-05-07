<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\CampaignSummary;

use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\CampaignSummary\CampaignSummaryApplicationEventUpdater;
use Fundrik\WordPress\Infrastructure\CampaignSummary\CampaignSummaryException;
use Fundrik\WordPress\Infrastructure\Ports\Database\DatabasePort;
use Fundrik\WordPress\Infrastructure\Stores\CampaignReadModelStore;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseException;
use Fundrik\WordPress\Tests\Fixtures\FakeDatabaseRowNotFoundException;
use Fundrik\WordPress\Tests\Fixtures\FakeDonationAlreadyExistsException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\Expectation;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( CampaignSummaryApplicationEventUpdater::class )]
#[CoversClass( CampaignSummaryException::class )]
final class CampaignSummaryApplicationEventUpdaterTest extends MockeryTestCase {

	private const string DONATION_ID = '019b6bcb-2f32-4461-838f-67a1479fbdbe';

	private DonationRepositoryPort&MockInterface $donations;
	private DatabasePort&MockInterface $database;
	private CampaignReadModelStore $store;
	private LoggerInterface&MockInterface $logger;
	private CampaignSummaryApplicationEventUpdater $consumer;

	protected function setUp(): void {

		parent::setUp();

		$this->donations = Mockery::mock( DonationRepositoryPort::class );
		$this->database = Mockery::mock( DatabasePort::class );
		$this->store = new CampaignReadModelStore( $this->database );
		$this->logger = Mockery::mock( LoggerInterface::class );
		$this->consumer = new CampaignSummaryApplicationEventUpdater( $this->donations, $this->store, $this->logger );
	}

	#[Test]
	public function consume_updates_campaign_summary_for_succeeded_donation(): void {

		$this->logger->shouldNotReceive( 'warning' );
		$this->logger->shouldNotReceive( 'error' );

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::on( static fn ( EntityId $id ): bool => $id->get_value() === self::DONATION_ID ) )
			->andReturn( self::donation( self::DONATION_ID, 77, 500, 'succeeded' ) );

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

		$this->consumer->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_updates_campaign_summary_for_refunded_donation(): void {

		$this->logger->shouldNotReceive( 'warning' );
		$this->logger->shouldNotReceive( 'error' );

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::on( static fn ( EntityId $id ): bool => $id->get_value() === self::DONATION_ID ) )
			->andReturn( self::donation( self::DONATION_ID, 77, 500, 'refunded' ) );

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

		$this->consumer->consume( new DonationRefundedEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_ignores_unsupported_donation_event(): void {

		$this->logger->shouldNotReceive( 'warning' );
		$this->logger->shouldNotReceive( 'error' );
		$this->donations->shouldNotReceive( 'find_by_id' );
		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->consumer->consume( new DonationCreatedEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_throws_when_donation_id_cannot_be_resolved(): void {

		$this->donations->shouldNotReceive( 'find_by_id' );
		$this->database->shouldNotReceive( 'apply_numeric_deltas' );
		$this->logger->shouldNotReceive( 'error' );

		/** Warning expectation.
		 *
		 * @var Expectation $logger_warning_expectation
		 */
		$logger_warning_expectation = $this->logger->shouldReceive( 'warning' );

		$logger_warning_expectation
			->once()
			->with(
				'Campaign summary update failed due to invalid donation ID.',
				Mockery::subset(
					[
						'service_class' => CampaignSummaryApplicationEventUpdater::class,
						'logger_class' => CampaignSummaryApplicationEventUpdater::class,
						'component' => 'campaign_summary',
						'layer' => 'infrastructure',
						'system' => 'wordpress',
						'operation' => 'update',
						'outcome' => 'invalid',
						'event_class' => DonationSucceededEvent::class,
						'reason' => 'donation_id_not_uuid',
					],
				),
			);

		$this->expectException( CampaignSummaryException::class );
		$this->expectExceptionMessage( 'Donation ID must be valid. Given: 11.' );

		$this->consumer->consume( new DonationSucceededEvent( EntityId::create( 11 ) ) );
	}

	#[Test]
	public function consume_throws_when_donation_lookup_fails(): void {

		$this->logger->shouldReceive( 'error' )->once();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( new FakeDonationAlreadyExistsException( self::DONATION_ID ) );

		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->expectException( CampaignSummaryException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to retrieve donation "%s".', self::DONATION_ID ) );

		$this->consumer->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_throws_when_donation_is_missing(): void {

		$this->logger->shouldReceive( 'error' )->once();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( null );

		$this->database->shouldNotReceive( 'apply_numeric_deltas' );

		$this->expectException( CampaignSummaryException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Cannot update campaign summary for donation "%s": donation not found.',
				self::DONATION_ID,
			),
		);

		$this->consumer->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_throws_when_campaign_row_is_missing(): void {

		$this->logger->shouldReceive( 'error' )->once();

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( self::donation( self::DONATION_ID, 77, 500, 'succeeded' ) );

		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->andThrow( new FakeDatabaseRowNotFoundException( 'Row not found.' ) );

		$this->expectException( CampaignSummaryException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Cannot update campaign summary for donation "%s": campaign "%d" not found.',
				self::DONATION_ID,
				77,
			),
		);

		$this->consumer->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	#[Test]
	public function consume_logs_error_and_rethrows_summary_update_failures(): void {

		$this->logger->shouldNotReceive( 'warning' );

		$this->donations
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::on( static fn ( EntityId $id ): bool => $id->get_value() === self::DONATION_ID ) )
			->andReturn( self::donation( self::DONATION_ID, 77, 500, 'succeeded' ) );

		$this->database
			->shouldReceive( 'apply_numeric_deltas' )
			->once()
			->andThrow( new FakeDatabaseException( 'DB failed.' ) );

		/** Error expectation.
		 *
		 * @var Expectation $logger_error_expectation
		 */
		$logger_error_expectation = $this->logger->shouldReceive( 'error' );

		$logger_error_expectation
			->once()
			->with(
				'Campaign summary update failed.',
				Mockery::on(
					static fn ( array $context ): bool => ( $context['service_class'] ?? null ) === CampaignSummaryApplicationEventUpdater::class
						&& ( $context['logger_class'] ?? null ) === CampaignSummaryApplicationEventUpdater::class
						&& ( $context['component'] ?? null ) === 'campaign_summary'
						&& ( $context['layer'] ?? null ) === 'infrastructure'
						// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Structured system value is intentionally lowercase.
						&& ( $context['system'] ?? null ) === 'wordpress'
						&& ( $context['operation'] ?? null ) === 'update'
						&& ( $context['outcome'] ?? null ) === 'failed'
						&& ( $context['event_class'] ?? null ) === DonationSucceededEvent::class
						&& ( $context['donation_event'] ?? null ) === 'succeeded'
						&& ( $context['donation_id'] ?? null ) === self::DONATION_ID
						&& ( $context['exception'] ?? null ) instanceof CampaignSummaryException,
				),
			);

		$this->expectException( CampaignSummaryException::class );
		$this->expectExceptionMessage( 'Failed to update campaign summary for campaign "77".' );

		$this->consumer->consume( new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) ) );
	}

	private static function donation( string $id, int $campaign_id, int $amount, string $status ) {

		return ( new DonationFactory() )->create_from_primitives(
			id: $id,
			version: 2,
			campaign_id: $campaign_id,
			amount: $amount,
			currency_code: 'RUB',
			status: $status,
		);
	}
}
