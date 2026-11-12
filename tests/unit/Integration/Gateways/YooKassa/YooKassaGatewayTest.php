<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways\YooKassa;

use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingFieldInterface;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingTextField;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGateway;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGatewayException;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaSettingsReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\Helpers\SettingOptionName;
use Fundrik\WordPress\Integration\WpSchemaType;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;
use YooKassa\Client;
use YooKassa\Model\Payment\Confirmation\ConfirmationRedirect;
use YooKassa\Request\Payments\CreatePaymentRequestInterface;
use YooKassa\Request\Payments\CreatePaymentResponse;

#[CoversClass( YooKassaGateway::class )]
#[CoversClass( YooKassaGatewayException::class )]
#[UsesClass( DonationId::class )]
#[UsesClass( GatewaySettingTextField::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( SettingOptionName::class )]
#[UsesClass( YooKassaSettingsReader::class )]
final class YooKassaGatewayTest extends WordPressTestCase {

	private Client&MockInterface $client;
	private YooKassaGateway $gateway;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->client = Mockery::mock( Client::class );
		$this->gateway = new YooKassaGateway(
			$this->client,
			new YooKassaSettingsReader(
				new OptionReader(
					new FakeStorage(
						[
							'fundrik_yookassa_shop_id_setting' => 'shop-id',
							'fundrik_yookassa_secret_key_setting' => 'secret-key',
						],
					),
				),
			),
		);
	}

	#[Test]
	public function get_id_returns_yookassa(): void {

		self::assertSame( 'yookassa', $this->gateway->get_id() );
	}

	#[Test]
	public function get_label_returns_yookassa(): void {

		self::assertSame( 'YooKassa', $this->gateway->get_label() );
	}

	#[Test]
	public function render_settings_description_outputs_yookassa_description(): void {

		ob_start();
		$this->gateway->render_settings_description();
		$output = ob_get_clean();

		self::assertSame( '<p>Configure YooKassa payment gateway settings.</p>', $output );
	}

	#[Test]
	public function get_settings_fields_returns_yookassa_text_fields(): void {

		$fields = $this->gateway->get_settings_fields();

		self::assertSame(
			[
				[
					'class' => GatewaySettingTextField::class,
					'id' => 'shop_id',
					'label' => 'YooKassa shop ID',
					'default' => '',
					'type' => WpSchemaType::String,
				],
				[
					'class' => GatewaySettingTextField::class,
					'id' => 'secret_key',
					'label' => 'YooKassa secret key',
					'default' => '',
					'type' => WpSchemaType::String,
				],
			],
			array_map(
				static fn ( GatewaySettingFieldInterface $field ): array => [
					'class' => $field::class,
					'id' => $field->get_id(),
					'label' => $field->get_label(),
					'default' => $field->get_default_value(),
					'type' => $field->get_value_type(),
				],
				$fields,
			),
		);
	}

	#[Test]
	public function create_checkout_creates_yookassa_payment_and_returns_checkout_result(): void {

		$request = $this->make_checkout_request();
		$payment_request = null;
		$confirmation = new ConfirmationRedirect(
			[
				'confirmation_url' => 'https://yookassa.example/redirect',
			],
		);
		$payment = Mockery::mock( CreatePaymentResponse::class );
		$payment->shouldReceive( 'getId' )->once()->andReturn( 'payment-123' );
		$payment->shouldReceive( 'getConfirmation' )->once()->andReturn( $confirmation );

		$this->client
			->shouldReceive( 'setAuth' )
			->once()
			->with( 'shop-id', 'secret-key' )
			->andReturnSelf();
		$this->client
			->shouldReceive( 'createPayment' )
			->once()
			->with( Mockery::capture( $payment_request ), '123e4567-e89b-42d3-a456-426614174001' )
			->andReturn( $payment );

		$result = $this->gateway->create_checkout( $request );

		self::assertInstanceOf( CreatePaymentRequestInterface::class, $payment_request );
		self::assertTrue( $payment_request->getCapture() );
		self::assertSame( 'Donation for campaign "Campaign"', $payment_request->getDescription() );

		$amount = $payment_request->getAmount();
		self::assertSame( '12.50', $amount?->getValue() );
		self::assertSame( 'RUB', $amount?->getCurrency() );

		$request_confirmation = $payment_request->getConfirmation();
		self::assertSame( 'redirect', $request_confirmation?->getType() );
		self::assertSame( 'https://example.test/success', $request_confirmation?->getReturnUrl() );

		$metadata = $payment_request->getMetadata();
		self::assertSame(
			[
				'donation_id' => '123e4567-e89b-42d3-a456-426614174001',
				'campaign_id' => 901,
				'success_url' => 'https://example.test/success',
				'cancel_url' => 'https://example.test/cancel',
			],
			$metadata?->toArray(),
		);
		self::assertSame( 'payment-123', $result->get_payment_id()->get_value() );
		self::assertSame( 'https://yookassa.example/redirect', $result->get_redirect_url()->get_value() );
	}

	#[Test]
	public function create_checkout_wraps_sdk_failure(): void {

		$request = $this->make_checkout_request();
		$previous = new RuntimeException( 'YooKassa is unavailable.' );

		$this->client
			->shouldReceive( 'setAuth' )
			->once()
			->with( 'shop-id', 'secret-key' )
			->andReturnSelf();
		$this->client
			->shouldReceive( 'createPayment' )
			->once()
			->with(
				Mockery::type( CreatePaymentRequestInterface::class ),
				'123e4567-e89b-42d3-a456-426614174001',
			)
			->andThrow( $previous );

		try {
			$this->gateway->create_checkout( $request );
		} catch ( YooKassaGatewayException $exception ) {
			self::assertSame(
				'Failed to create checkout for donation "123e4567-e89b-42d3-a456-426614174001".',
				$exception->getMessage(),
			);
			self::assertSame( $previous, $exception->getPrevious() );

			return;
		}

		self::fail( 'Expected YooKassaGatewayException was not thrown.' );
	}

	#[Test]
	public function create_checkout_wraps_missing_payment_response(): void {

		$this->client
			->shouldReceive( 'setAuth' )
			->once()
			->with( 'shop-id', 'secret-key' )
			->andReturnSelf();
		$this->client
			->shouldReceive( 'createPayment' )
			->once()
			->with(
				Mockery::type( CreatePaymentRequestInterface::class ),
				'123e4567-e89b-42d3-a456-426614174001',
			)
			->andReturnNull();

		$this->expectException( YooKassaGatewayException::class );
		$this->expectExceptionMessage(
			'Failed to create checkout for donation "123e4567-e89b-42d3-a456-426614174001".',
		);

		$this->gateway->create_checkout( $this->make_checkout_request() );
	}

	private function make_checkout_request(): DonationGatewayCheckoutRequest {

		return new DonationGatewayCheckoutRequest(
			EntityId::create( '123e4567-e89b-42d3-a456-426614174001' ),
			EntityId::create( 901 ),
			Money::create( 1_250, 'RUB' ),
			'Donation for campaign "Campaign"',
			Url::create( 'https://example.test/success' ),
			Url::create( 'https://example.test/cancel' ),
		);
	}
}
