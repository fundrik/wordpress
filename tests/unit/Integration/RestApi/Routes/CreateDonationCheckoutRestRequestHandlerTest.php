<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi\Routes;

use Brain\Monkey\Functions;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign as CoreCampaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationPendingEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutResult;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Exceptions\InvalidUrlException;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Components\Donations\Domain\Exceptions\InvalidDonationIdException;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\RestApi\Routes\CreateDonationCheckoutRestRequestHandler;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Tests\Fixtures\FakeDonationGatewayException;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

#[CoversClass( CreateDonationCheckoutRestRequestHandler::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( DonationId::class )]
#[UsesClass( InvalidDonationIdException::class )]
#[UsesClass( Logger::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( CheckoutCancelUrlSetting::class )]
#[UsesClass( CheckoutSuccessUrlSetting::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SettingOptionName::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( RestRouteHandlerLogger::class )]
#[UsesClass( CampaignLookupService::class )]
final class CreateDonationCheckoutRestRequestHandlerTest extends WordPressTestCase {

	private const string DONATION_ID = '123e4567-e89b-42d3-a456-426614174000';
	private const int CAMPAIGN_ID = 42;
	private const int AMOUNT = 1_250;
	private const string PAYMENT_ID = 'payment-5001';
	private const string SUCCESS_URL = 'https://example.test/donation/success/';
	private const string CANCEL_URL = 'https://example.test/donation/cancel/';
	private const string REDIRECT_URL = 'https://payment.example.test/checkout/payment-5001';

	private CampaignReadPort&MockInterface $campaign_read;
	private CampaignRepositoryPort&MockInterface $campaign_repository;
	private DonationRepositoryPort&MockInterface $donation_repository;
	private DonationGatewayPort&MockInterface $gateway;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private LoggerInterface&MockInterface $psr_logger;
	private FakeStorage $storage;
	private CreateDonationCheckoutRestRequestHandler $handler;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->gateway = Mockery::mock( DonationGatewayPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->storage = new FakeStorage(
			[
				'fundrik_checkout_success_url_setting' => self::SUCCESS_URL,
				'fundrik_checkout_cancel_url_setting' => self::CANCEL_URL,
			],
		);

		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/campaigns/help-the-shelter/' );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 91 );

		$this->handler = new CreateDonationCheckoutRestRequestHandler(
			new RestRouteHandlerLogger( $this->psr_logger ),
			$this->make_checkout_handler(),
			$this->make_settings_reader(),
			$this->make_campaign_lookup(),
		);
	}

	#[Test]
	public function handle_creates_checkout_and_returns_created_response(): void {

		$this->expect_campaign_lookup( $this->make_core_campaign_read_model() );
		$this->expect_donation_created();

		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->with( Mockery::on( $this->matches_checkout_request( ... ) ) )
			->andReturn(
				new DonationGatewayCheckoutResult(
					PaymentId::create( self::PAYMENT_ID ),
					Url::create( self::REDIRECT_URL ),
				),
			);

		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->with( Mockery::type( Donation::class ) )
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( DonationPendingEvent::class ) );

		$response = $this->handler->handle( $this->make_request() );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 201, $response->get_status() );
		self::assertSame(
			[
				'donation_id' => self::DONATION_ID,
				'campaign_id' => self::CAMPAIGN_ID,
				'amount' => self::AMOUNT,
				'currency' => 'RUB',
				'payment_id' => self::PAYMENT_ID,
				'redirect_url' => self::REDIRECT_URL,
			],
			$response->get_data(),
		);
	}

	#[Test]
	public function handle_returns_invalid_parameter_error_for_invalid_request(): void {

		$this->expect_log(
			level: 'warning',
			message: 'Create-donation REST request payload is invalid.',
			extra: [
				'operation' => 'normalize_request',
				'outcome' => 'invalid',
			],
			exception_class: InvalidDonationIdException::class,
			exception_message: 'Donation ID must be a valid UUIDv4. Given: invalid.',
		);

		$result = $this->handler->handle( $this->make_request( donation_id: 'invalid' ) );

		$this->assert_rest_error(
			$result,
			'rest_invalid_param',
			'Donation ID must be a valid UUIDv4. Given: invalid.',
			400,
		);
	}

	#[Test]
	public function handle_returns_invalid_parameter_error_when_campaign_does_not_exist(): void {

		$this->expect_campaign_lookup( null );
		$this->expect_log(
			level: 'warning',
			message: 'Create-donation REST request payload is invalid.',
			extra: [
				'operation' => 'normalize_request',
				'outcome' => 'invalid',
			],
			exception_class: InvalidArgumentException::class,
			exception_message: 'Campaign must exist. Given: 42.',
		);

		$result = $this->handler->handle( $this->make_request() );

		$this->assert_rest_error( $result, 'rest_invalid_param', 'Campaign must exist. Given: 42.', 400 );
	}

	#[Test]
	public function handle_returns_checkout_failure_when_configured_url_is_invalid(): void {

		$this->storage->set( 'fundrik_checkout_success_url_setting', 'invalid-url' );
		$this->expect_campaign_lookup( $this->make_core_campaign_read_model() );
		$this->expect_log(
			level: 'error',
			message: 'Create-donation checkout URL is invalid.',
			extra: [
				'operation' => 'normalize_request',
				'outcome' => 'failed',
				'stage' => 'url',
			],
			exception_class: InvalidUrlException::class,
			exception_message: 'URL must be a valid URL. Given: "invalid-url".',
		);

		$result = $this->handler->handle( $this->make_request() );

		$this->assert_rest_error(
			$result,
			'fundrik_checkout_failed',
			'URL must be a valid URL. Given: "invalid-url".',
			500,
		);
	}

	#[Test]
	public function handle_returns_checkout_failure_for_unexpected_request_normalization_error(): void {

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->andThrow( new RuntimeException( 'Campaign database exploded.' ) );

		$this->expect_log(
			level: 'error',
			message: 'Create-donation checkout request failed unexpectedly.',
			extra: [
				'operation' => 'normalize_request',
				'outcome' => 'failed',
				'stage' => 'unexpected',
			],
			exception_class: RuntimeException::class,
			exception_message: 'Campaign database exploded.',
		);

		$result = $this->handler->handle( $this->make_request() );

		$this->assert_rest_error( $result, 'fundrik_checkout_failed', 'Campaign database exploded.', 500 );
	}

	#[Test]
	public function handle_returns_checkout_failure_when_checkout_creation_fails(): void {

		$this->expect_campaign_lookup( $this->make_core_campaign_read_model() );
		$this->expect_donation_created();

		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andThrow( new FakeDonationGatewayException( 'Gateway rejected checkout.' ) );

		$this->expect_log(
			level: 'error',
			message: 'Creating donation from REST request failed.',
			extra: [
				'operation' => 'create_donation',
				'outcome' => 'failed',
				'campaign_id' => self::CAMPAIGN_ID,
				'amount' => self::AMOUNT,
			],
			exception_class: CreateDonationCheckoutException::class,
			exception_message: sprintf( 'Failed to create checkout for donation "%s".', self::DONATION_ID ),
		);

		$result = $this->handler->handle( $this->make_request() );

		$this->assert_rest_error(
			$result,
			'fundrik_checkout_failed',
			sprintf( 'Failed to create checkout for donation "%s".', self::DONATION_ID ),
			500,
		);
	}

	#[Test]
	public function handle_returns_checkout_failure_for_unexpected_checkout_error(): void {

		$this->expect_campaign_lookup( $this->make_core_campaign_read_model() );
		$this->expect_donation_created();

		$this->gateway
			->shouldReceive( 'create_checkout' )
			->once()
			->andThrow( new RuntimeException( 'Gateway exploded.' ) );

		$this->expect_log(
			level: 'error',
			message: 'Create-donation checkout request failed unexpectedly.',
			extra: [
				'operation' => 'create_donation',
				'outcome' => 'failed',
				'stage' => 'unexpected',
			],
			exception_class: RuntimeException::class,
			exception_message: 'Gateway exploded.',
		);

		$result = $this->handler->handle( $this->make_request() );

		$this->assert_rest_error( $result, 'fundrik_checkout_failed', 'Gateway exploded.', 500 );
	}

	private function make_checkout_handler(): CreateDonationCheckoutHandler {

		return new CreateDonationCheckoutHandler(
			new CreateDonationIdempotentlyHandler(
				new CreateDonationHandler(
					$this->campaign_repository,
					new DonationFactory(),
					$this->donation_repository,
					$this->event_bus,
				),
				new FindDonationByIdHandler( $this->donation_repository ),
			),
			$this->gateway,
			$this->donation_repository,
			$this->event_bus,
		);
	}

	private function make_settings_reader(): AdminSettingsReader {

		$group = Mockery::mock( AdminSettingsGroupInterface::class );
		$group
			->shouldReceive( 'get_settings' )
			->once()
			->andReturn(
				[
					new CheckoutSuccessUrlSetting(),
					new CheckoutCancelUrlSetting(),
				],
			);
		$group
			->shouldReceive( 'get_id' )
			->twice()
			->andReturn( 'checkout' );

		return new AdminSettingsReader( new OptionReader( $this->storage ), $group );
	}

	private function make_campaign_lookup(): CampaignLookupService {

		return new CampaignLookupService(
			new CampaignQueryService( new ReadCampaignByIdHandler( $this->campaign_read ) ),
			Mockery::mock( WordPressRuntimeInterface::class ),
			Mockery::mock( LoggerInterface::class ),
		);
	}

	private function make_request(
		string $donation_id = self::DONATION_ID,
		int $campaign_id = self::CAMPAIGN_ID,
		int $amount = self::AMOUNT,
	): WP_REST_Request {

		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'donation_id', $donation_id );
		$request->set_param( 'campaign_id', $campaign_id );
		$request->set_param( 'amount', $amount );

		return $request;
	}

	private function expect_campaign_lookup( ?CoreCampaign $campaign ): void {

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === self::CAMPAIGN_ID,
				),
			)
			->andReturn( $campaign );
	}

	private function expect_donation_created(): void {

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === self::CAMPAIGN_ID,
				),
			)
			->andReturn(
				$this->make_campaign(
					id: self::CAMPAIGN_ID,
					accepts_donations: true,
					currency_code: 'RUB',
				),
			);

		$this->donation_repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Donation::class ) )
			->andReturnUsing( static fn ( Donation $donation ): Donation => $donation );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( DonationCreatedEvent::class ) );
	}

	private function matches_checkout_request( DonationGatewayCheckoutRequest $request ): bool {

		return $request->get_donation_id()->get_value() === self::DONATION_ID
			&& $request->get_campaign_id()->get_value() === self::CAMPAIGN_ID
			&& $request->get_money()->get_amount()->get_value() === self::AMOUNT
			&& $request->get_money()->get_currency()->get_code() === 'RUB'
			&& $request->get_payment_description() === 'Donation for campaign "Help the shelter"'
			&& $request->get_success_url()->get_value() === self::SUCCESS_URL
			&& $request->get_cancel_url()->get_value() === self::CANCEL_URL;
	}

	/**
	 * Expects a structured REST route handler log entry.
	 *
	 * @param array<string, mixed> $extra Additional log context.
	 * @param class-string<Throwable> $exception_class Expected exception class.
	 */
	private function expect_log(
		string $level,
		string $message,
		array $extra,
		string $exception_class,
		string $exception_message,
	): void {

		$expected_context = [
			'service_class' => CreateDonationCheckoutRestRequestHandler::class,
			'logger_class' => RestRouteHandlerLogger::class,
			'component' => 'rest_route_handlers',
			'layer' => 'integration',
			'system' => 'wordpress',
		] + $extra;

		$this->psr_logger
			->shouldReceive( $level )
			->once()
			->with(
				$message,
				Mockery::on(
					static function ( array $context ) use (
						$expected_context,
						$exception_class,
						$exception_message,
					): bool {

						$exception = $context['exception'] ?? null;
						unset( $context['exception'] );

						return $exception instanceof $exception_class
							&& $exception->getMessage() === $exception_message
							&& $context === $expected_context;
					},
				),
			);
	}

	private function assert_rest_error(
		WP_REST_Response|WP_Error $result,
		string $code,
		string $message,
		int $status,
	): void {

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( $code, $result->get_error_code() );
		self::assertSame( $message, $result->get_error_message() );
		self::assertSame( [ 'status' => $status ], $result->get_error_data() );
	}
}
