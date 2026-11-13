<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi\Routes;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\Core\Components\Donations\Application\Events\DonationRefundedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationRejectedEvent;
use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationByPaymentId\FindDonationByPaymentIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultPolicy;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Donations\Domain\PaymentId;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRequestHandler;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use YooKassa\Client;
use YooKassa\Model\Notification\NotificationEventType;

#[CoversClass( YooKassaNotifyRestRequestHandler::class )]
#[UsesClass( Logger::class )]
#[UsesClass( RestRouteHandlerLogger::class )]
final class YooKassaNotifyRestRequestHandlerTest extends WordPressTestCase {

	private const string DONATION_ID = '123e4567-e89b-42d3-a456-426614174000';
	private const string PAYMENT_ID = '11111111-1111-4111-8111-111111111111';
	private const string REFUND_ID = '22222222-2222-4222-8222-222222222222';
	private const string REMOTE_ADDRESS = '185.71.76.1';

	private Client&MockInterface $client;
	private DonationRepositoryPort&MockInterface $donations;
	private ApplicationEventBusPort&MockInterface $event_bus;
	private LoggerInterface&MockInterface $psr_logger;
	private YooKassaNotifyRestRequestHandler $handler;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->client = Mockery::mock( Client::class );
		$this->donations = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		Functions\when( 'wp_unslash' )->returnArg();

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		$_SERVER['REMOTE_ADDR'] = self::REMOTE_ADDRESS;

		$this->handler = new YooKassaNotifyRestRequestHandler(
			$this->client,
			new RestRouteHandlerLogger( $this->psr_logger ),
			$this->make_process_payment_result_handler(),
			new FindDonationByPaymentIdHandler( $this->donations ),
		);
	}

	#[Override]
	protected function tearDown(): void {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		unset( $_SERVER['REMOTE_ADDR'] );

		parent::tearDown();
	}

	#[Test]
	public function handle_returns_bad_request_for_invalid_payload(): void {

		$result = $this->handler->handle( $this->make_request( 'not-json' ) );

		$this->assert_rest_error(
			$result,
			'fundrik_yookassa_invalid_notification_payload',
			'YooKassa notification payload must decode to an array with string keys. Given: invalid payload.',
			400,
		);
	}

	#[Test]
	public function handle_rejects_notification_when_ip_is_not_trusted(): void {

		$payload = $this->make_payment_payload( NotificationEventType::PAYMENT_SUCCEEDED );
		$request = $this->make_request( $payload );

		$this->expect_ip_verification( $request, $payload, false );

		$result = $this->handler->handle( $request );

		$this->assert_rest_error(
			$result,
			'fundrik_notification_verification_failed',
			'YooKassa notification verification failed.',
			403,
		);
	}

	#[Test]
	public function handle_rejects_notification_when_ip_verification_fails(): void {

		$payload = $this->make_payment_payload( NotificationEventType::PAYMENT_SUCCEEDED );
		$request = $this->make_request( $payload );

		$this->client
			->shouldReceive( 'isNotificationIPTrusted' )
			->once()
			->with( self::REMOTE_ADDRESS )
			->andThrow( new RuntimeException( 'IP verification failed.' ) );

		Filters\expectApplied( 'fundrik_yookassa_notification_verified_by_ip' )
			->once()
			->with( false, $request, $payload );

		$result = $this->handler->handle( $request );

		$this->assert_rest_error(
			$result,
			'fundrik_notification_verification_failed',
			'YooKassa notification verification failed.',
			403,
		);
	}

	#[Test]
	public function handle_rejects_notification_when_remote_address_is_not_a_string(): void {

		$payload = $this->make_payment_payload( NotificationEventType::PAYMENT_SUCCEEDED );
		$request = $this->make_request( $payload );

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		$_SERVER['REMOTE_ADDR'] = [ self::REMOTE_ADDRESS ];

		Filters\expectApplied( 'fundrik_yookassa_notification_verified_by_ip' )
			->once()
			->with( false, $request, $payload );

		$result = $this->handler->handle( $request );

		$this->assert_rest_error(
			$result,
			'fundrik_notification_verification_failed',
			'YooKassa notification verification failed.',
			403,
		);
	}

	#[Test]
	public function handle_uses_filtered_ip_verification_result(): void {

		$payload = $this->make_payment_payload( NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE );
		$request = $this->make_request( $payload );

		$this->client
			->shouldReceive( 'isNotificationIPTrusted' )
			->once()
			->with( self::REMOTE_ADDRESS )
			->andReturn( false );

		Filters\expectApplied( 'fundrik_yookassa_notification_verified_by_ip' )
			->once()
			->with( false, $request, $payload )
			->andReturn( true );

		$result = $this->handler->handle( $request );

		$this->assert_rest_response( $result, [ 'status' => 'ignored' ] );
	}

	#[Test]
	public function handle_uses_original_ip_verification_for_invalid_filter_result(): void {

		$payload = $this->make_payment_payload( NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE );
		$request = $this->make_request( $payload );

		$this->client
			->shouldReceive( 'isNotificationIPTrusted' )
			->once()
			->with( self::REMOTE_ADDRESS )
			->andReturn( true );

		Filters\expectApplied( 'fundrik_yookassa_notification_verified_by_ip' )
			->once()
			->with( true, $request, $payload )
			->andReturn( 'invalid' );

		Functions\expect( '_doing_it_wrong' )
			->once()
			->with(
				'fundrik_yookassa_notification_verified_by_ip',
				'Filter must return a boolean. Given: string.',
				'1.0.0',
			);

		$result = $this->handler->handle( $request );

		$this->assert_rest_response( $result, [ 'status' => 'ignored' ] );
	}

	#[Test]
	public function handle_ignores_invalid_notification_data(): void {

		$payload = [ 'type' => 'notification' ];
		$request = $this->make_request( $payload );

		$this->expect_ip_verification( $request, $payload, true );

		$result = $this->handler->handle( $request );

		$this->assert_rest_response( $result, [ 'status' => 'ignored' ] );
	}

	#[Test]
	public function handle_ignores_unsupported_notification_event(): void {

		$payload = $this->make_payment_payload( NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE );
		$request = $this->make_request( $payload );

		$this->expect_ip_verification( $request, $payload, true );

		$result = $this->handler->handle( $request );

		$this->assert_rest_response( $result, [ 'status' => 'ignored' ] );
	}

	#[Test]
	#[DataProvider( 'supported_notification_cases' )]
	public function handle_processes_supported_notification(
		string $event,
		DonationStatus $source_status,
		DonationStatus $expected_status,
		string $expected_event_class,
	): void {

		$payload = $event === NotificationEventType::REFUND_SUCCEEDED
			? $this->make_refund_payload()
			: $this->make_payment_payload( $event );
		$request = $this->make_request( $payload );
		$donation = $source_status === DonationStatus::Pending
			? $this->make_pending_donation( id: self::DONATION_ID, payment_id: self::PAYMENT_ID )
			: $this->make_succeeded_donation( id: self::DONATION_ID, payment_id: self::PAYMENT_ID );

		$this->expect_ip_verification( $request, $payload, true );
		$this->expect_donation_processing( $donation, $expected_status, $expected_event_class );

		$result = $this->handler->handle( $request );

		$this->assert_rest_response( $result, [ 'status' => 'processed' ] );
	}

	#[Test]
	public function handle_logs_error_and_returns_failure_when_donation_is_not_found(): void {

		$payload = $this->make_payment_payload( NotificationEventType::PAYMENT_SUCCEEDED );
		$request = $this->make_request( $payload );

		$this->expect_ip_verification( $request, $payload, true );

		$this->donations
			->shouldReceive( 'find_by_payment_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( PaymentId $id ): bool => $id->get_value() === self::PAYMENT_ID,
				),
			)
			->andReturn( null );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'YooKassa notification processing failed.',
				Mockery::on( $this->matches_processing_failure_log( ... ) ),
			);

		$result = $this->handler->handle( $request );

		$this->assert_rest_error(
			$result,
			'fundrik_notify_failed',
			sprintf( 'Cannot process notification for payment "%s": donation not found.', self::PAYMENT_ID ),
			500,
		);
	}

	public static function supported_notification_cases(): iterable {

		yield 'succeeded payment' => [
			NotificationEventType::PAYMENT_SUCCEEDED,
			DonationStatus::Pending,
			DonationStatus::Succeeded,
			DonationSucceededEvent::class,
		];

		yield 'canceled payment' => [
			NotificationEventType::PAYMENT_CANCELED,
			DonationStatus::Pending,
			DonationStatus::Rejected,
			DonationRejectedEvent::class,
		];

		yield 'succeeded refund' => [
			NotificationEventType::REFUND_SUCCEEDED,
			DonationStatus::Succeeded,
			DonationStatus::Refunded,
			DonationRefundedEvent::class,
		];
	}

	private function make_process_payment_result_handler(): ProcessDonationPaymentResultHandler {

		return new ProcessDonationPaymentResultHandler(
			new FindDonationByIdHandler( $this->donations ),
			new ProcessDonationPaymentResultPolicy(),
			new SucceedDonationHandler( $this->donations, $this->event_bus ),
			new RejectDonationHandler( $this->donations, $this->event_bus ),
			new RefundDonationHandler( $this->donations, $this->event_bus ),
		);
	}

	private function make_request( array|string $payload ): WP_REST_Request {

		$body = is_array( $payload )
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			? json_encode( $payload, JSON_THROW_ON_ERROR )
			: $payload;

		$request = new WP_REST_Request( 'POST' );
		$request->set_body( $body );

		return $request;
	}

	private function make_payment_payload( string $event ): array {

		$status = match ( $event ) {
			NotificationEventType::PAYMENT_SUCCEEDED => 'succeeded',
			NotificationEventType::PAYMENT_CANCELED => 'canceled',
			default => 'waiting_for_capture',
		};

		return [
			'type' => 'notification',
			'event' => $event,
			'object' => [
				'id' => self::PAYMENT_ID,
				'status' => $status,
				'amount' => [
					'value' => '12.50',
					'currency' => 'RUB',
				],
				'created_at' => '2026-08-04T00:00:00.000Z',
				'paid' => $status === 'succeeded',
				'refundable' => false,
				'test' => true,
				'recipient' => [
					'account_id' => '123456',
				],
			],
		];
	}

	private function make_refund_payload(): array {

		return [
			'type' => 'notification',
			'event' => NotificationEventType::REFUND_SUCCEEDED,
			'object' => [
				'id' => self::REFUND_ID,
				'payment_id' => self::PAYMENT_ID,
				'status' => 'succeeded',
				'amount' => [
					'value' => '12.50',
					'currency' => 'RUB',
				],
				'created_at' => '2026-08-04T00:00:00.000Z',
			],
		];
	}

	private function expect_ip_verification( WP_REST_Request $request, array $payload, bool $verified, ): void {

		$this->client
			->shouldReceive( 'isNotificationIPTrusted' )
			->once()
			->with( self::REMOTE_ADDRESS )
			->andReturn( $verified );

		Filters\expectApplied( 'fundrik_yookassa_notification_verified_by_ip' )
			->once()
			->with( $verified, $request, $payload );
	}

	private function expect_donation_processing(
		Donation $donation,
		DonationStatus $expected_status,
		string $expected_event_class,
	): void {

		$this->donations
			->shouldReceive( 'find_by_payment_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( PaymentId $id ): bool => $id->get_value() === self::PAYMENT_ID,
				),
			)
			->andReturn( $donation );

		$this->donations
			->shouldReceive( 'find_by_id' )
			->twice()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === self::DONATION_ID,
				),
			)
			->andReturn( $donation );

		$this->donations
			->shouldReceive( 'update' )
			->once()
			->with(
				Mockery::on(
					static fn ( Donation $updated ): bool => $updated->get_status() === $expected_status,
				),
			)
			->andReturnUsing( static fn ( Donation $updated ): Donation => $updated );

		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( $expected_event_class ) );
	}

	private function matches_processing_failure_log( array $context ): bool {

		$exception = $context['exception'] ?? null;
		unset( $context['exception'] );

		return $exception instanceof RuntimeException
			&& $exception->getMessage() === sprintf(
				'Cannot process notification for payment "%s": donation not found.',
				self::PAYMENT_ID,
			)
			&& $context === [
				'service_class' => YooKassaNotifyRestRequestHandler::class,
				'logger_class' => RestRouteHandlerLogger::class,
				'component' => 'rest_route_handlers',
				'layer' => 'integration',
				'system' => 'wordpress',
				'operation' => 'process_notification',
				'outcome' => 'failed',
			];
	}

	private function assert_rest_response( WP_REST_Response|WP_Error $result, array $data ): void {

		self::assertInstanceOf( WP_REST_Response::class, $result );
		self::assertSame( 200, $result->get_status() );
		self::assertSame( $data, $result->get_data() );
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
