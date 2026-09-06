<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways\YooKassa;

use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingCheckboxField;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingTextField;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGateway;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGatewayException;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGatewaySettings;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaSettingsReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use YooKassa\Client;
use YooKassa\Model\Payment\Confirmation\ConfirmationRedirect;
use YooKassa\Request\Payments\CreatePaymentRequest;
use YooKassa\Request\Payments\CreatePaymentRequestInterface;
use YooKassa\Request\Payments\CreatePaymentResponse;

#[CoversClass( YooKassaGateway::class )]
#[UsesClass( DonationGatewayCheckoutRequest::class )]
#[UsesClass( CreatePaymentRequest::class )]
#[UsesClass( CreatePaymentResponse::class )]
#[UsesClass( ConfirmationRedirect::class )]
#[UsesClass( GatewaySettingCheckboxField::class )]
#[UsesClass( GatewaySettingTextField::class )]
final class YooKassaGatewayTest extends MockeryTestCase {

	private StoragePort&MockInterface $storage;

	private Client&MockInterface $client;

	private YooKassaSettingsReader $settings_reader;

	private YooKassaGatewaySettings $gateway_settings;

	protected function setUp(): void {

		parent::setUp();

		$this->storage = Mockery::mock( StoragePort::class );
		$this->client = Mockery::mock( Client::class );
		$this->settings_reader = new YooKassaSettingsReader( new OptionReader( $this->storage ) );
		$this->gateway_settings = new YooKassaGatewaySettings( $this->settings_reader );
	}

	#[Test]
	public function it_creates_checkout_sessions_via_the_sdk_client(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174001';
		$campaign_id = '123e4567-e89b-42d3-a456-426614174002';

		$request = new DonationGatewayCheckoutRequest(
			EntityId::create( $donation_id ),
			EntityId::create( $campaign_id ),
			Money::create( 1_250, 'RUB' ),
			'Donation for campaign "Name"',
			Url::create( 'https://example.com/success' ),
			Url::create( 'https://example.com/cancel' ),
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

		$confirmation = new ConfirmationRedirect(
			[
				'confirmation_url' => 'https://yookassa.example/redirect',
			],
		);

		$payment = Mockery::mock( CreatePaymentResponse::class );
		$payment
			->shouldReceive( 'getConfirmation' )
			->once()
			->andReturn( $confirmation );

		$this->client
			->shouldReceive( 'setAuth' )
			->once()
			->with( 'shop-id', 'secret-key' );
		$this->client
			->shouldReceive( 'createPayment' )
			->once()
			->with(
				Mockery::on(
					static function ( object $payment_data ) use ( $donation_id, $campaign_id ): bool {

						if ( ! $payment_data instanceof CreatePaymentRequestInterface ) {
							return false;
						}

						$amount = $payment_data->getAmount();
						$confirmation = $payment_data->getConfirmation();
						$metadata = $payment_data->getMetadata();

						return $amount !== null
							&& $amount->getValue() === '12.50'
							&& $amount->getCurrency() === 'RUB'
							&& $payment_data->getCapture() === true
							&& $payment_data->getDescription() === 'Donation for campaign "Name"'
							&& $confirmation !== null
							&& $confirmation->getType() === 'redirect'
							&& $confirmation->getReturnUrl() === 'https://example.com/success'
							&& $metadata !== null
							&& $metadata->toArray()['donation_id'] === $donation_id
							&& $metadata->toArray()['campaign_id'] === $campaign_id
							&& $metadata->toArray()['success_url'] === 'https://example.com/success'
							&& $metadata->toArray()['cancel_url'] === 'https://example.com/cancel';
					},
				),
				$donation_id,
			)
			->andReturn( $payment );

		$gateway = new YooKassaGateway( $this->client, $this->gateway_settings );
		$result = $gateway->create_checkout( $request );

		self::assertSame( 'https://yookassa.example/redirect', $result->get_redirect_url()->get_value() );
	}

	#[Test]
	public function it_rejects_checkout_creation_when_credentials_are_missing(): void {

		$donation_id = '123e4567-e89b-42d3-a456-426614174001';
		$campaign_id = '123e4567-e89b-42d3-a456-426614174002';

		$request = new DonationGatewayCheckoutRequest(
			EntityId::create( $donation_id ),
			EntityId::create( $campaign_id ),
			Money::create( 1_250, 'RUB' ),
			'Donation for campaign "Name"',
			Url::create( 'https://example.com/success' ),
			Url::create( 'https://example.com/cancel' ),
		);

		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_shop_id_setting' )
			->andReturn( '' );
		$this->storage
			->shouldReceive( 'get' )
			->once()
			->with( 'fundrik_yookassa_secret_key_setting' )
			->andReturn( 'secret-key' );
		$this->client
			->shouldReceive( 'setAuth' )
			->once()
			->with( '', 'secret-key' );
		$this->client
			->shouldReceive( 'createPayment' )
			->once()
			->andThrow( new \RuntimeException( 'Authorization headers not set' ) );

		$this->expectException( YooKassaGatewayException::class );
		$this->expectExceptionMessage( sprintf( 'Failed to create checkout for donation "%s".', $donation_id ) );

		$gateway = new YooKassaGateway( $this->client, $this->gateway_settings );
		$gateway->create_checkout( $request );
	}

	#[Test]
	public function it_exposes_the_admin_settings_group_configuration(): void {

		$gateway = new YooKassaGateway( $this->client, $this->gateway_settings );
		$fields = $gateway->get_settings_fields();

		self::assertCount( 4, $fields );
		self::assertInstanceOf( GatewaySettingCheckboxField::class, $fields[0] );
		self::assertSame( 'enabled', $fields[0]->get_id() );
		self::assertSame( 'Enable YooKassa', $fields[0]->get_label() );
		self::assertFalse( $fields[0]->get_default_value() );
		self::assertInstanceOf( GatewaySettingCheckboxField::class, $fields[1] );
		self::assertSame( 'test_mode', $fields[1]->get_id() );
		self::assertSame( 'YooKassa test mode', $fields[1]->get_label() );
		self::assertTrue( $fields[1]->get_default_value() );
		self::assertInstanceOf( GatewaySettingTextField::class, $fields[2] );
		self::assertSame( 'shop_id', $fields[2]->get_id() );
		self::assertSame( 'YooKassa shop ID', $fields[2]->get_label() );
		self::assertSame( '', $fields[2]->get_default_value() );
		self::assertInstanceOf( GatewaySettingTextField::class, $fields[3] );
		self::assertSame( 'secret_key', $fields[3]->get_id() );
		self::assertSame( 'YooKassa secret key', $fields[3]->get_label() );
		self::assertSame( '', $fields[3]->get_default_value() );
	}
}
