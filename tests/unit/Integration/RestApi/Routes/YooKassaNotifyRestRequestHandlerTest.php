<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi\Routes;

use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation as DonationReadModel;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResultType;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultPolicy;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultStatus;
use Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById\ReadDonationByIdHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation\RefundDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation\RejectDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation\SucceedDonationHandler;
use Fundrik\Core\Components\Donations\Domain\Donation as DonationDomain;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaSettingsReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRequestHandler;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use YooKassa\Model\AmountInterface;
use YooKassa\Model\Metadata;
use YooKassa\Client;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationFactory;
use YooKassa\Model\Payment\PaymentInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

#[CoversClass( YooKassaNotifyRestRequestHandler::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( YooKassaSettingsReader::class )]
#[UsesClass( DonationReadModel::class )]
#[UsesClass( DonationDomain::class )]
#[UsesClass( DonationSucceededEvent::class )]
#[UsesClass( NotificationFactory::class )]
#[UsesClass( ProcessDonationPaymentResult::class )]
final class YooKassaNotifyRestRequestHandlerTest extends MockeryTestCase {

	private Client&MockInterface $client;

	private StoragePort&MockInterface $storage;

	private YooKassaSettingsReader $settings_reader;

	private DonationReadPort&MockInterface $donation_read;

	private DonationRepositoryPort&MockInterface $donation_repository;

	private ApplicationEventBusPort&MockInterface $event_bus;

	private ProcessDonationPaymentResultHandler $process_payment_result;

	protected function setUp(): void {

		parent::setUp();

		$this->client = Mockery::mock( Client::class );
		$this->storage = Mockery::mock( StoragePort::class );
		$this->settings_reader = new YooKassaSettingsReader( new OptionReader( $this->storage ) );
		$this->donation_read = Mockery::mock( DonationReadPort::class );
		$this->donation_repository = Mockery::mock( DonationRepositoryPort::class );
		$this->event_bus = Mockery::mock( ApplicationEventBusPort::class );
		$this->process_payment_result = new ProcessDonationPaymentResultHandler(
			new ReadDonationByIdHandler( $this->donation_read ),
			new ProcessDonationPaymentResultPolicy(),
			new SucceedDonationHandler( $this->donation_repository, $this->event_bus ),
			new RejectDonationHandler( $this->donation_repository, $this->event_bus ),
			new RefundDonationHandler( $this->donation_repository, $this->event_bus ),
		);
	}

	#[Test]
	public function it_processes_succeeded_payment_notifications(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174000';
		$payment_id = '11111111-1111-4111-8111-111111111111';

		$request = $this->make_request(
			$this->make_notification_payload( NotificationEventType::PAYMENT_SUCCEEDED, $donation_id ),
		);

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_shop_id_setting' )
			->andReturn( 'shop-id' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_secret_key_setting' )
			->andReturn( 'secret-key' );
		$this->client
			->shouldReceive( 'setAuth' )
			->once()
			->with( 'shop-id', 'secret-key' );
		$this->client
			->shouldReceive( 'getPaymentInfo' )
			->once()
			->with( $payment_id )
			->andReturn( $this->make_verified_payment( $payment_id, 'succeeded', $donation_id ) );
		$this->donation_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::on( static fn ( EntityId $id ): bool => $id->get_value() === $donation_id ) )
			->andReturn(
				new DonationReadModel(
					$donation_id,
					'123e4567-e89b-42d3-a456-426614174001',
					1250,
					'RUB',
					DonationStatus::Pending->value,
					UtcDateTime::now(),
				),
			);
		$this->donation_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::on( static fn ( EntityId $id ): bool => $id->get_value() === $donation_id ) )
			->andReturn( $this->make_pending_donation( $donation_id ) );
		$this->donation_repository
			->shouldReceive( 'update' )
			->once()
			->with(
				Mockery::on(
					static function ( DonationDomain $updated ) use ( $donation_id ): bool {

						return $updated->get_id()->get_value() === $donation_id
							&& $updated->get_status() === DonationStatus::Succeeded;
					},
				),
			)
			->andReturnUsing(
				static fn ( DonationDomain $donation ): DonationDomain => $donation,
			);
		$this->event_bus
			->shouldReceive( 'publish' )
			->once()
			->with( Mockery::type( DonationSucceededEvent::class ) );

		$handler = new YooKassaNotifyRestRequestHandler( $this->client, $this->settings_reader, $this->process_payment_result );
		$response = $handler->handle( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 200, $response->get_status() );
		self::assertSame(
			[
				'status' => ProcessDonationPaymentResultStatus::Applied->value,
				'result_type' => DonationPaymentResultType::Succeeded->value,
				'donation_id' => $donation_id,
			],
			$response->get_data(),
		);
	}

	#[Test]
	public function it_ignores_unsupported_notification_events(): void {

		$request = $this->make_request(
			$this->make_notification_payload(
				NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE,
				'123e4567-e89b-42d3-a456-426614174000',
			),
		);

		$this->storage->shouldNotReceive( 'get' );
		$this->client
			->shouldNotReceive( 'setAuth' );
		$this->client->shouldNotReceive( 'getPaymentInfo' );
		$this->client->shouldNotReceive( 'getRefundInfo' );
		$this->donation_read->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'update' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$handler = new YooKassaNotifyRestRequestHandler( $this->client, $this->settings_reader, $this->process_payment_result );
		$response = $handler->handle( $request );

		self::assertInstanceOf( WP_REST_Response::class, $response );
		self::assertSame( 200, $response->get_status() );
		self::assertSame( [ 'status' => 'ignored' ], $response->get_data() );
	}

	#[Test]
	public function it_rejects_notifications_when_verified_status_does_not_match_payload(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174000';
		$payment_id = '11111111-1111-4111-8111-111111111111';
		$request = $this->make_request(
			$this->make_notification_payload( NotificationEventType::PAYMENT_SUCCEEDED, $donation_id ),
		);

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_shop_id_setting' )
			->andReturn( 'shop-id' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_secret_key_setting' )
			->andReturn( 'secret-key' );
		$this->client
			->shouldReceive( 'setAuth' )
			->once()
			->with( 'shop-id', 'secret-key' );
		$this->client
			->shouldReceive( 'getPaymentInfo' )
			->once()
			->with( $payment_id )
			->andReturn( $this->make_verified_payment( $payment_id, 'pending', $donation_id ) );
		$this->donation_read->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'find_by_id' );
		$this->donation_repository->shouldNotReceive( 'update' );
		$this->event_bus->shouldNotReceive( 'publish' );

		$handler = new YooKassaNotifyRestRequestHandler( $this->client, $this->settings_reader, $this->process_payment_result );
		$response = $handler->handle( $request );

		self::assertInstanceOf( WP_Error::class, $response );
		self::assertSame( 400, $response->get_error_data()['status'] );
	}

	/**
	 * @param array<string, mixed> $payload Notification payload.
	 *
	 * @return WP_REST_Request Request stub.
	 */
	private function make_request( array $payload ): WP_REST_Request {

		$request = Mockery::mock( WP_REST_Request::class );
		$request->shouldReceive( 'get_body' )->andReturn( json_encode( $payload ) ?: '' );

		return $request;
	}

	/**
	 * @param string $event YooKassa notification event.
	 * @param string $donation_id Donation ID metadata value.
	 *
	 * @return array<string, mixed> Notification payload.
	 */
	private function make_notification_payload( string $event, string $donation_id ): array {

		return [
			'type' => 'notification',
			'event' => $event,
			'object' => [
				'id' => '11111111-1111-4111-8111-111111111111',
				'status' => 'succeeded',
				'amount' => [
					'value' => '10.00',
					'currency' => 'RUB',
				],
				'created_at' => '2026-08-04T00:00:00.000Z',
				'paid' => true,
				'refundable' => false,
				'test' => true,
				'recipient' => [
					'account_id' => '123456',
				],
				'confirmation' => [
					'type' => 'redirect',
					'confirmation_url' => 'https://example.com/redirect',
				],
				'metadata' => [
					'donation_id' => $donation_id,
				],
			],
		];
	}

	/**
	 * @param string $donation_id Donation ID.
	 *
	 * @return DonationDomain Pending donation aggregate.
	 */
	private function make_pending_donation( string $donation_id ): DonationDomain {

		return new DonationDomain(
			EntityId::create( $donation_id ),
			EntityVersion::initial(),
			EntityId::create( '123e4567-e89b-42d3-a456-426614174001' ),
			Money::create( 1250, 'RUB' ),
			DonationStatus::Pending,
		);
	}

	/**
	 * @param string $payment_id Payment ID.
	 * @param string $status Payment status.
	 * @param string $donation_id Donation ID.
	 *
	 * @return PaymentInterface Verified payment object.
	 */
	private function make_verified_payment( string $payment_id, string $status, string $donation_id ): PaymentInterface {

		$amount = Mockery::mock( AmountInterface::class );
		$amount
			->shouldReceive( 'getValue' )
			->andReturn( '10.00' );
		$amount
			->shouldReceive( 'getCurrency' )
			->andReturn( 'RUB' );

		$metadata = Mockery::mock( Metadata::class );
		$metadata
			->shouldReceive( 'toArray' )
			->andReturn( [ 'donation_id' => $donation_id ] );

		$payment = Mockery::mock( PaymentInterface::class );
		$payment
			->shouldReceive( 'getId' )
			->andReturn( $payment_id );
		$payment
			->shouldReceive( 'getStatus' )
			->andReturn( $status );
		$payment
			->shouldReceive( 'getAmount' )
			->andReturn( $amount );
		$payment
			->shouldReceive( 'getMetadata' )
			->andReturn( $metadata );

		return $payment;
	}
}
