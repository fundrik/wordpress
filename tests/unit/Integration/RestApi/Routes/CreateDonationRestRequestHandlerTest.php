<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi\Routes;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationRepositoryException;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\RestApi\Routes\CreateDonationRestRequestHandler;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use WP_Error;
use WP_REST_Request;

#[CoversClass( CreateDonationRestRequestHandler::class )]
#[UsesClass( RestRouteHandlerLogger::class )]
#[UsesClass( CreateDonationIdempotentlyHandler::class )]
#[UsesClass( FindDonationByIdHandler::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( CampaignFactory::class )]
final class CreateDonationRestRequestHandlerTest extends WordPressTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private CreateDonationRestRequestHandler $handler;
	private LoggerInterface&MockInterface $psr_logger;
	private CampaignFactory $campaign_factory;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->campaign_factory = new CampaignFactory();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->psr_logger->shouldIgnoreMissing();

		$logger = new RestRouteHandlerLogger( $this->psr_logger );

		$this->handler = new CreateDonationRestRequestHandler(
			self::new_create_donation_idempotently_handler(
				$this->campaign_repository,
				$this->donation_repository,
				$this->event_bus,
			),
			$logger,
		);
	}

	#[Test]
	public function handle_logs_with_rest_route_handler_logger_class(): void {

		$donation_id = '123e4567-e89b-12d3-a456-426614174010';

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->make_campaign( 33, true ) );

		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Donation::class ) )
			->andThrow( new DonationRepositoryException( 'DB failed.' ) );

		$this->event_bus->shouldNotReceive( 'publish' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Creating donation from REST request failed.',
				Mockery::on(
					// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Expected logger context value.
					static fn ( array $context ): bool => ( $context['service_class'] ?? null ) === CreateDonationRestRequestHandler::class
							&& ( $context['logger_class'] ?? null ) === RestRouteHandlerLogger::class
							&& ( $context['component'] ?? null ) === 'rest_route_handlers'
							&& ( $context['layer'] ?? null ) === 'integration'
							// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Expected logger context value.
							&& ( $context['system'] ?? null ) === 'wordpress'
							&& ( $context['operation'] ?? null ) === 'create_donation'
							&& ( $context['outcome'] ?? null ) === 'failed'
							&& ( $context['campaign_id'] ?? null ) === 33
							&& ( $context['amount_minor'] ?? null ) === 1_250
							&& ( $context['exception'] ?? null ) instanceof CreateDonationException,
				),
			);

		$response = $this->handler->handle(
			$this->make_request(
				[
					'donation_id' => $donation_id,
					'campaign_id' => 33,
					'amount' => 1_250,
				],
			),
		);

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'fundrik_donation_create_failed', $response->get_error_code() );
		self::assertSame( 500, $response->get_error_data()['status'] ?? null );
	}

	#[Test]
	public function handle_logs_invalid_payload_normalization_as_warning(): void {

		$this->campaign_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'insert' );
		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Create-donation REST request payload is invalid.',
				Mockery::on(
					// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Expected logger context value.
					static fn ( array $context ): bool => ( $context['service_class'] ?? null ) === CreateDonationRestRequestHandler::class
							&& ( $context['logger_class'] ?? null ) === RestRouteHandlerLogger::class
							&& ( $context['component'] ?? null ) === 'rest_route_handlers'
							&& ( $context['layer'] ?? null ) === 'integration'
							// phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledInText -- Expected logger context value.
							&& ( $context['system'] ?? null ) === 'wordpress'
							&& ( $context['operation'] ?? null ) === 'normalize_request'
							&& ( $context['outcome'] ?? null ) === 'invalid'
							&& ( $context['exception'] ?? null ) instanceof InvalidArgumentException,
				),
			);

		$response = $this->handler->handle(
			$this->make_request(
				[
					'donation_id' => '123e4567-e89b-12d3-a456-426614174010',
					'campaign_id' => 33,
					'amount' => 'invalid',
				],
			),
		);

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'rest_invalid_param', $response->get_error_code() );
		self::assertSame( 'Value must be int. Given: string.', $response->get_error_message() );
		self::assertSame( 400, $response->get_error_data()['status'] ?? null );
	}

	#[Test]
	public function handle_returns_invalid_param_error_for_non_positive_campaign_id(): void {

		$this->campaign_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'insert' );
		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$response = $this->handler->handle(
			$this->make_request(
				[
					'donation_id' => '123e4567-e89b-12d3-a456-426614174010',
					'campaign_id' => 0,
					'amount' => 100,
				],
			),
		);

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'rest_invalid_param', $response->get_error_code() );
		self::assertSame( 'Campaign ID must be a positive integer. Given: 0.', $response->get_error_message() );
		self::assertSame( 400, $response->get_error_data()['status'] ?? null );
	}

	private function make_request( array $payload ): WP_REST_Request {

		$request = new WP_REST_Request( 'POST', '/fundrik/v1/donations' );

		foreach ( $payload as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	private function make_campaign( int $id, bool $accepts_donations ): Campaign {

		return $this->campaign_factory->create_from_primitives(
			id: $id,
			version: 1,
			title: 'Campaign',
			accepts_donations: $accepts_donations,
			currency_code: 'RUB',
			target_amount: null,
		);
	}

	private static function new_create_donation_idempotently_handler(
		CampaignRepositoryPort $campaign_repository,
		DonationRepositoryPort $donation_repository,
		ApplicationEventBusPort $event_bus,
	): CreateDonationIdempotentlyHandler {

		$create_donation = new CreateDonationHandler(
			$campaign_repository,
			new DonationFactory(),
			$donation_repository,
			$event_bus,
		);

		return new CreateDonationIdempotentlyHandler(
			$create_donation,
			new FindDonationByIdHandler( $donation_repository ),
		);
	}
}
