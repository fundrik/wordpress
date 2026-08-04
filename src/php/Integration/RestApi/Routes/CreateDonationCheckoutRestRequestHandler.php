<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout\CreateDonationCheckoutResult;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Shared\Application\Exceptions\InvalidUrlException;
use Fundrik\Core\Components\Shared\Application\Url;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGateway;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaSettingsReader;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles donation checkout REST requests.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CreateDonationCheckoutRestRequestHandler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationIdempotentlyHandler $create_donation_idempotently Handles idempotent donation creation.
	 * @param YooKassaSettingsReader $settings_reader Reads YooKassa settings.
	 * @param YooKassaGateway $gateway Creates checkout sessions.
	 */
	public function __construct(
		private CreateDonationIdempotentlyHandler $create_donation_idempotently,
		private YooKassaSettingsReader $settings_reader,
		private YooKassaGateway $gateway,
	) {}

	/**
	 * Creates a checkout from REST request payload.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 *
	 * @return WP_REST_Response|WP_Error Checkout result payload or error details.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		try {
			$data = $this->create_checkout_data( $request );
		} catch ( InvalidUrlException $e ) {
			return new WP_Error( 'fundrik_checkout_failed', $e->getMessage(), [ 'status' => 500 ] );
		} catch ( Throwable $e ) {
			return new WP_Error( 'rest_invalid_param', $e->getMessage(), [ 'status' => 400 ] );
		}

		try {
			$result = ( new CreateDonationCheckoutHandler(
				$this->create_donation_idempotently,
				$this->gateway,
			) )->handle( $data );
		} catch ( CreateDonationCheckoutException $e ) {
			return new WP_Error( 'fundrik_checkout_failed', $e->getMessage(), [ 'status' => 500 ] );
		}

		return $this->create_checkout_response( $result );
	}

	/**
	 * Creates checkout input data from the REST request.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 *
	 * @return CreateDonationCheckoutData Checkout input data.
	 */
	private function create_checkout_data( WP_REST_Request $request ): CreateDonationCheckoutData {

		$donation_id = DonationId::from_value( TypeCaster::to_string( $request->get_param( 'donation_id' ) ) );
		$campaign_id = CampaignId::from_value( TypeCaster::to_int( $request->get_param( 'campaign_id' ) ) );
		$amount = TypeCaster::to_int( $request->get_param( 'amount' ) );

		return new CreateDonationCheckoutData(
			new DonationCreationData(
				donation_id: $donation_id->to_entity_id(),
				campaign_id: $campaign_id->to_entity_id(),
				amount: Amount::create( $amount ),
			),
			Url::create( $this->settings_reader->get_success_url() ),
			Url::create( $this->settings_reader->get_cancel_url() ),
		);
	}

	/**
	 * Creates the checkout response payload.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationCheckoutResult $result Checkout result.
	 *
	 * @return WP_REST_Response Checkout response.
	 */
	private function create_checkout_response( CreateDonationCheckoutResult $result ): WP_REST_Response {

		return new WP_REST_Response(
			[
				'donation_id' => $result->get_donation_id()->get_value(),
				'campaign_id' => $result->get_campaign_id()->get_value(),
				'amount' => $result->get_money()->get_amount()->get_value(),
				'currency' => $result->get_money()->get_currency()->get_code(),
				'redirect_url' => $result->get_redirect_url()->get_value(),
			],
			201,
		);
	}
}
