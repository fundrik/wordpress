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
use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGateway;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaSettingsReader;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use InvalidArgumentException;
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
	 * @param RestRouteHandlerLogger $logger Writes structured log entries for REST route handler operations.
	 * @param CreateDonationIdempotentlyHandler $create_donation_idempotently Handles idempotent donation creation.
	 * @param YooKassaSettingsReader $settings_reader Reads YooKassa settings.
	 * @param YooKassaGateway $gateway Creates checkout sessions.
	 * @param CampaignLookupService $campaign_lookup Resolves campaign data for checkout metadata.
	 */
	public function __construct(
		private RestRouteHandlerLogger $logger,
		private CreateDonationIdempotentlyHandler $create_donation_idempotently,
		private YooKassaSettingsReader $settings_reader,
		private YooKassaGateway $gateway,
		private CampaignLookupService $campaign_lookup,
	) {

		$this->logger->set_rest_route_handler_class( self::class );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
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
			$this->log_invalid_checkout_url( $e );
			return new WP_Error( 'fundrik_checkout_failed', $e->getMessage(), [ 'status' => 500 ] );
		} catch ( FundrikDomainException | InvalidArgumentException $e ) {
			$this->log_invalid_request( $e );
			return new WP_Error( 'rest_invalid_param', $e->getMessage(), [ 'status' => 400 ] );
		} catch ( Throwable $e ) {
			$this->log_unexpected_failure( $e );
			return new WP_Error( 'fundrik_checkout_failed', $e->getMessage(), [ 'status' => 500 ] );
		}

		try {
			$result = ( new CreateDonationCheckoutHandler(
				$this->create_donation_idempotently,
				$this->gateway,
			) )->handle( $data );
		} catch ( CreateDonationCheckoutException $e ) {
			$donation_creation_data = $data->get_donation_creation_data();

			$this->log_create_donation_failed(
				$donation_creation_data->get_campaign_id()->get_value(),
				$donation_creation_data->get_amount()->get_value(),
				$e,
			);

			return new WP_Error( 'fundrik_checkout_failed', $e->getMessage(), [ 'status' => 500 ] );
		}

		return $this->create_checkout_response( $result );
	}
	// phpcs:enable

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
			$this->create_payment_description( $campaign_id ),
			Url::create( $this->settings_reader->get_success_url() ),
			Url::create( $this->settings_reader->get_cancel_url() ),
		);
	}

	/**
	 * Creates the payment description for the given campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignId $campaign_id Campaign ID.
	 *
	 * @return string Payment description.
	 *
	 * @throws InvalidArgumentException When the campaign cannot be resolved.
	 */
	private function create_payment_description( CampaignId $campaign_id ): string {

		$campaign = $this->campaign_lookup->get( $campaign_id->get_value() );

		if ( $campaign === null ) {
			throw new InvalidArgumentException(
				sprintf( 'Campaign must exist. Given: %d.', $campaign_id->get_value() ),
			);
		}

		return sprintf( 'Donation for campaign "%s"', $campaign->get_title() );
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

	/**
	 * Logs an invalid checkout URL for create-donation requests (error).
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $exception Original URL validation exception.
	 */
	private function log_invalid_checkout_url( Throwable $exception ): void {

		$this->logger->log_error(
			'Create-donation checkout URL is invalid.',
			[
				'operation' => 'normalize_request',
				'outcome' => 'failed',
				'stage' => 'url',
				'exception' => $exception,
			],
		);
	}

	/**
	 * Logs an invalid create-donation request payload (warning).
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $exception Original payload normalization exception.
	 */
	private function log_invalid_request( Throwable $exception ): void {

		$this->logger->log_warning(
			'Create-donation REST request payload is invalid.',
			[
				'operation' => 'normalize_request',
				'outcome' => 'invalid',
				'exception' => $exception,
			],
		);
	}

	/**
	 * Logs an unexpected failure for create-donation checkout requests (error).
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $exception Original unexpected exception.
	 */
	private function log_unexpected_failure( Throwable $exception ): void {

		$this->logger->log_error(
			'Create-donation checkout request failed unexpectedly.',
			[
				'operation' => 'normalize_request',
				'outcome' => 'failed',
				'stage' => 'unexpected',
				'exception' => $exception,
			],
		);
	}

	/**
	 * Logs a create-donation request failure (error).
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign ID.
	 * @param int $amount Donation amount in minor units.
	 * @param Throwable $exception Original create-donation exception.
	 */
	private function log_create_donation_failed( int $campaign_id, int $amount, Throwable $exception ): void {

		$this->logger->log_error(
			'Creating donation from REST request failed.',
			[
				'operation' => 'create_donation',
				'outcome' => 'failed',
				'campaign_id' => $campaign_id,
				'amount' => $amount,
				'exception' => $exception,
			],
		);
	}
}
