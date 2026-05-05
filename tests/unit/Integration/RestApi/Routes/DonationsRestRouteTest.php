<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi\Routes;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Repositories\DonationRepository\DonationRepositoryException;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\RestApi\Routes\CreateDonationRestRequestHandler;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Fundrik\WordPress\Tests\Fixtures\FakeDonationAlreadyExistsException;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

#[CoversClass( DonationsRestRoute::class )]
#[UsesClass( RestRouteHandlerLogger::class )]
#[UsesClass( DonationFactory::class )]
#[UsesClass( CampaignFactory::class )]
#[UsesClass( CreateDonationRestRequestHandler::class )]
final class DonationsRestRouteTest extends WordPressTestCase {

	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private DonationsRestRoute $route;
	private CampaignFactory $campaign_factory;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->campaign_factory = new CampaignFactory();

		$psr_logger = Mockery::mock( LoggerInterface::class );
		$psr_logger->shouldIgnoreMissing();

		$logger = new RestRouteHandlerLogger( $psr_logger );

		$request_handler = new CreateDonationRestRequestHandler(
			self::new_create_donation_idempotently_handler(
				$this->campaign_repository,
				$this->donation_repository,
				$this->event_bus,
			),
			$logger,
		);

		$this->route = new DonationsRestRoute( $request_handler );
	}

	#[Test]
	public function route_exposes_expected_registration_definition(): void {

		$args = $this->route->get_route_args();
		$endpoint = $args[0] ?? null;
		self::assertIsArray( $endpoint );
		self::assertSame( WP_REST_Server::CREATABLE, $endpoint['methods'] ?? null );
		self::assertIsCallable( $endpoint['permission_callback'] ?? null );
		self::assertIsCallable( $endpoint['callback'] ?? null );
		self::assertIsArray( $endpoint['args'] ?? null );
		self::assertSame( 'string', $endpoint['args']['donation_id']['type'] ?? null );
		self::assertSame( 'integer', $endpoint['args']['campaign_id']['type'] ?? null );
		self::assertSame( 'integer', $endpoint['args']['amount']['type'] ?? null );
		self::assertTrue( $endpoint['args']['donation_id']['required'] ?? false );
		self::assertTrue( ( $endpoint['permission_callback'] )() );
		self::assertArrayNotHasKey( 'schema', $args );
	}

	#[Test]
	public function create_donation_returns_created_response_for_valid_payload(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174000';

		$this->donation_repository->shouldNotReceive( 'find_by_id' );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 15,
				),
			)
			->andReturn( $this->make_campaign( 15, true ) );

		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Donation::class ) )
			->andReturnUsing(
				static fn ( Donation $donation ): Donation => $donation,
			);

		$this->event_bus->shouldReceive( 'publish' )->once();

		$request = $this->make_request(
			[
				'donation_id' => $donation_id,
				'campaign_id' => 15,
				'amount' => 1_500,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 201, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertSame( $donation_id, $data['id'] ?? null );
		self::assertSame( 15, $data['campaign_id'] ?? null );
		self::assertSame( 1_500, $data['amount'] ?? null );
		self::assertSame( 'pending', $data['status'] ?? null );
	}

	#[Test]
	public function create_donation_returns_validation_error_for_invalid_amount(): void {

		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->campaign_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'insert' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$request = $this->make_request(
			[
				'donation_id' => '123e4567-e89b-42d3-a456-426614174001',
				'campaign_id' => 15,
				'amount' => 0,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'rest_invalid_param', $response->get_error_code() );
		self::assertSame( 400, $response->get_error_data()['status'] ?? null );
	}

	#[Test]
	public function create_donation_returns_conflict_when_campaign_is_closed(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174002';

		$this->donation_repository->shouldNotReceive( 'find_by_id' );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->make_campaign( 21, false ) );

		$this->donation_repository->shouldNotReceive( 'insert' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$request = $this->make_request(
			[
				'donation_id' => $donation_id,
				'campaign_id' => 21,
				'amount' => 500,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'fundrik_campaign_cannot_receive_donations', $response->get_error_code() );
		self::assertSame( 409, $response->get_error_data()['status'] ?? null );
	}

	#[Test]
	public function create_donation_replays_existing_donation_when_create_collides_on_duplicate_id(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174003';
		$existing_donation = $this->make_donation( $donation_id, 22, 500 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === $donation_id,
				),
			)
			->andReturn( $existing_donation );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->make_campaign( 22, true ) );

		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Donation::class ) )
			->andThrow( new FakeDonationAlreadyExistsException( 'Duplicate.' ) );

		$this->event_bus->shouldNotReceive( 'publish' );

		$request = $this->make_request(
			[
				'donation_id' => $donation_id,
				'campaign_id' => 22,
				'amount' => 500,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 201, $response->get_status() );

		$data = $response->get_data();

		self::assertIsArray( $data );
		self::assertSame( $donation_id, $data['id'] ?? null );
		self::assertSame( 22, $data['campaign_id'] ?? null );
		self::assertSame( 500, $data['amount'] ?? null );
	}

	#[Test]
	public function create_donation_returns_internal_error_for_generic_persistence_failure(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174005';

		$this->donation_repository->shouldNotReceive( 'find_by_id' );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->make_campaign( 22, true ) );

		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Donation::class ) )
			->andThrow( new DonationRepositoryException( 'DB failed.' ) );

		$this->event_bus->shouldNotReceive( 'publish' );

		$request = $this->make_request(
			[
				'donation_id' => $donation_id,
				'campaign_id' => 22,
				'amount' => 500,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'fundrik_donation_create_failed', $response->get_error_code() );
		self::assertSame( 500, $response->get_error_data()['status'] ?? null );
	}

	#[Test]
	public function create_donation_returns_conflict_when_donation_id_is_reused_for_different_payload(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174004';
		$existing_donation = $this->make_donation( $donation_id, 22, 500 );

		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === $donation_id,
				),
			)
			->andReturn( $existing_donation );

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andReturn( $this->make_campaign( 22, true ) );
		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Donation::class ) )
			->andThrow( new FakeDonationAlreadyExistsException( 'Duplicate.' ) );
		$this->event_bus->shouldNotReceive( 'publish' );

		$request = $this->make_request(
			[
				'donation_id' => $donation_id,
				'campaign_id' => 22,
				'amount' => 700,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'fundrik_donation_request_conflict', $response->get_error_code() );
		self::assertSame( 409, $response->get_error_data()['status'] ?? null );
	}

	#[Test]
	public function create_donation_rejects_invalid_donation_id(): void {

		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->campaign_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'insert' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$request = $this->make_request(
			[
				'donation_id' => 'not-a-uuid',
				'campaign_id' => 22,
				'amount' => 500,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'rest_invalid_param', $response->get_error_code() );
		self::assertSame( 400, $response->get_error_data()['status'] ?? null );
	}

	#[Test]
	public function create_donation_rejects_non_uuidv4_donation_id(): void {

		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->campaign_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'insert' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$request = $this->make_request(
			[
				'donation_id' => '123e4567-e89b-72d3-a456-426614174000',
				'campaign_id' => 22,
				'amount' => 500,
			],
		);

		$response = $this->dispatch_request( $request );

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 'rest_invalid_param', $response->get_error_code() );
		self::assertSame( 400, $response->get_error_data()['status'] ?? null );
	}

	private function dispatch_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$args = $this->route->get_route_args();
		$endpoint = $args[0] ?? [];
		$request->set_attributes( $endpoint );
		$request->set_default_params( [] );

		$validation_result = $request->has_valid_params();

		if ( $validation_result instanceof WP_Error ) {
			return $validation_result;
		}

		$sanitization_result = $request->sanitize_params();

		if ( $sanitization_result instanceof WP_Error ) {
			return $sanitization_result;
		}

		self::assertIsCallable( $endpoint['callback'] ?? null );

		return ( $endpoint['callback'] )( $request );
	}

	private function make_request( array $json_payload ): WP_REST_Request {

		$request = new WP_REST_Request( 'POST', RestRouteDefinitions::get_request_path( DonationsRestRoute::class ) );

		foreach ( $json_payload as $key => $value ) {
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

	private function make_donation( string $donation_id, int $campaign_id, int $amount_minor ): Donation {

		return ( new DonationFactory() )->create_pending_from_primitives(
			id: $donation_id,
			campaign_id: $campaign_id,
			amount: $amount_minor,
			currency_code: 'RUB',
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
