<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways\YooKassa;

use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutRequest;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayCheckoutResult;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingFieldInterface;
use Fundrik\WordPress\Integration\Gateways\SettingFields\GatewaySettingTextField;
use Override;
use Throwable;
use YooKassa\Client;
use YooKassa\Model\Payment\ConfirmationType;
use YooKassa\Request\Payments\CreatePaymentRequest;
use YooKassa\Request\Payments\CreatePaymentRequestInterface;

/**
 * Creates YooKassa donation checkout sessions.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class YooKassaGateway implements GatewayInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Client $client YooKassa API client.
	 * @param YooKassaSettingsReader $settings Reads YooKassa runtime settings.
	 */
	public function __construct(
		private Client $client,
		private YooKassaSettingsReader $settings,
	) {}

	/**
	 * Returns the gateway ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Gateway ID.
	 */
	#[Override]
	public function get_id(): string {

		return 'yookassa';
	}

	/**
	 * Returns the gateway label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Gateway label.
	 */
	#[Override]
	public function get_label(): string {

		return 'YooKassa';
	}

	/**
	 * Renders the YooKassa settings description.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function render_settings_description(): void {

		echo '<p>' . esc_html__( 'Configure YooKassa payment gateway settings.', 'fundrik' ) . '</p>';
	}

	/**
	 * Returns the gateway settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @return list<GatewaySettingFieldInterface> Gateway settings fields.
	 */
	#[Override]
	public function get_settings_fields(): array {

		return [
			new GatewaySettingTextField(
				id: 'shop_id',
				label: __( 'YooKassa shop ID', 'fundrik' ),
				default_value: '',
			),
			new GatewaySettingTextField(
				id: 'secret_key',
				label: __( 'YooKassa secret key', 'fundrik' ),
				default_value: '',
			),
		];
	}

	/**
	 * Creates a YooKassa checkout session.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationGatewayCheckoutRequest $request Normalized checkout input.
	 *
	 * @return DonationGatewayCheckoutResult Normalized checkout output.
	 *
	 * @throws YooKassaGatewayException When checkout creation fails.
	 */
	#[Override]
	public function create_checkout( DonationGatewayCheckoutRequest $request ): DonationGatewayCheckoutResult {

		$donation_id = DonationId::from_entity_id( $request->get_donation_id() );
		$this->client->setAuth( $this->settings->get_shop_id(), $this->settings->get_secret_key() );

		try {
			$payment = $this->client->createPayment(
				$this->create_payment_request( $request ),
				$donation_id->get_value(),
			);

			return new DonationGatewayCheckoutResult(
				Url::create( $payment->getConfirmation()->getConfirmationUrl() ),
			);
		} catch ( Throwable $e ) {
			throw new YooKassaGatewayException(
				sprintf( 'Failed to create checkout for donation "%s".', $donation_id->get_value() ),
				previous: $e,
			);
		}
	}

	/**
	 * Creates a YooKassa payment request.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationGatewayCheckoutRequest $request Normalized checkout input.
	 *
	 * @return CreatePaymentRequestInterface YooKassa payment request.
	 */
	private function create_payment_request( DonationGatewayCheckoutRequest $request ): CreatePaymentRequestInterface {

		$money = $request->get_money();

		return CreatePaymentRequest::builder()
			->setAmount( $this->format_amount( $money ), $money->get_currency()->get_code() )
			->setCapture( true )
			->setConfirmation(
				[
					'type' => ConfirmationType::REDIRECT,
					'return_url' => $request->get_success_url()->get_value(),
				],
			)
			->setDescription( $request->get_payment_description() )
			->setMetadata(
				[
					'donation_id' => $request->get_donation_id()->get_value(),
					'campaign_id' => $request->get_campaign_id()->get_value(),
					'success_url' => $request->get_success_url()->get_value(),
					'cancel_url' => $request->get_cancel_url()->get_value(),
				],
			)
			->build();
	}

	/**
	 * Formats a donation amount in minor units for YooKassa.
	 *
	 * @since 1.0.0
	 *
	 * @param Money $money Donation money.
	 *
	 * @return string Amount formatted for YooKassa.
	 */
	private function format_amount( Money $money ): string {

		return number_format( $money->get_amount()->get_value() / 100, 2, '.', '' );
	}
}
