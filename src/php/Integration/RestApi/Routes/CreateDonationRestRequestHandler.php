<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationPreconditionReason;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyConflictException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use InvalidArgumentException;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles the donation creation REST request lifecycle for route-validated payloads.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class CreateDonationRestRequestHandler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationIdempotentlyHandler $create_donation_idempotently Handles idempotent donation creation.
	 * @param RestRouteHandlerLogger $logger Writes structured log entries for request handling.
	 */
	public function __construct(
		private CreateDonationIdempotentlyHandler $create_donation_idempotently,
		private RestRouteHandlerLogger $logger,
	) {

		$this->logger->set_rest_route_handler_class( self::class );
	}

	/**
	 * Creates a donation from REST request payload.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 *
	 * @return WP_REST_Response|WP_Error Created donation payload or error details.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		try {
			$payload = new CreateDonationRestRequestData(
				donation_id: DonationId::from_value( TypeCaster::to_string( $request->get_param( 'donation_id' ) ) ),
				campaign_id: CampaignId::from_value( TypeCaster::to_int( $request->get_param( 'campaign_id' ) ) ),
				amount: TypeCaster::to_int( $request->get_param( 'amount' ) ),
			);
		} catch ( FundrikDomainException | InvalidArgumentException $e ) {
			return $this->build_invalid_request_error( $e );
		}

		return $this->create_donation( $payload );
	}

	/**
	 * Creates and persists the donation for the incoming REST request.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationRestRequestData $payload Normalized request payload.
	 *
	 * @return WP_REST_Response|WP_Error Created donation payload or failure response.
	 */
	private function create_donation( CreateDonationRestRequestData $payload ): WP_REST_Response|WP_Error {

		try {
			$data = new DonationCreationData(
				donation_id: $payload->donation_id->to_entity_id(),
				campaign_id: $payload->campaign_id->to_entity_id(),
				amount: Amount::create( $payload->amount ),
			);
		} catch ( InvalidAmountException $e ) {
			return $this->build_invalid_request_error( $e );
		}

		try {
			$result = $this->create_donation_idempotently->handle( $data );
		} catch ( CreateDonationIdempotentlyConflictException ) {
			return $this->build_donation_request_conflict_error( $payload );
		} catch ( CreateDonationException $e ) {
			return $this->build_create_donation_error_response( $payload, $e );
		}

		return $this->build_created_response( $result->get_donation() );
	}

	/**
	 * Creates a validation error response for malformed request payload.
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $exception Payload validation failure.
	 *
	 * @return WP_Error REST API validation error payload.
	 */
	private function build_invalid_request_error( Throwable $exception ): WP_Error {

		$this->logger->log_invalid_create_donation_request( $exception );

		return new WP_Error(
			'rest_invalid_param',
			$exception->getMessage(),
			[ 'status' => 400 ],
		);
	}

	/**
	 * Maps use-case failures to REST API responses.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationRestRequestData $payload Normalized request payload.
	 * @param CreateDonationException $exception Original use-case failure.
	 *
	 * @return WP_Error REST API error payload.
	 */
	private function build_create_donation_error_response(
		CreateDonationRestRequestData $payload,
		CreateDonationException $exception,
	): WP_Error {

		$campaign_id = $payload->campaign_id->get_value();
		$reason = $exception->get_reason();

		if ( $reason === CreateDonationPreconditionReason::CampaignNotFound ) {
			return $this->build_campaign_not_found_error( $campaign_id );
		}

		if ( $reason === CreateDonationPreconditionReason::CampaignDoesNotAcceptDonations ) {
			return $this->build_campaign_cannot_receive_donations_error( $campaign_id );
		}

		$this->logger->log_create_donation_failed( $campaign_id, $payload->amount, $exception );

		return $this->build_failed_creation_error( $campaign_id );
	}

	/**
	 * Builds a created response payload from persisted donation entity.
	 *
	 * @since 1.0.0
	 *
	 * @param Donation $donation Created or replayed donation.
	 *
	 * @return WP_REST_Response Created response payload.
	 */
	private function build_created_response( Donation $donation ): WP_REST_Response {

		return new WP_REST_Response(
			[
				'id' => DonationId::from_entity_id( $donation->get_id() )->get_value(),
				'campaign_id' => CampaignId::from_entity_id( $donation->get_campaign_id() )->get_value(),
				'amount' => $donation->get_money()->get_amount()->get_value(),
				'status' => $donation->get_status()->value,
			],
			201,
		);
	}

	/**
	 * Creates a stable error response when donation creation fails unexpectedly.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign identifier from request payload.
	 */
	private function build_failed_creation_error( int $campaign_id ): WP_Error {

		return new WP_Error(
			'fundrik_donation_create_failed',
			sprintf( 'Failed to create donation for campaign "%d".', $campaign_id ),
			[ 'status' => 500 ],
		);
	}

	/**
	 * Creates a not-found error response for missing campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign identifier from request payload.
	 */
	private function build_campaign_not_found_error( int $campaign_id ): WP_Error {

		return new WP_Error(
			'fundrik_campaign_not_found',
			sprintf( 'Cannot create donation for campaign "%d": campaign not found.', $campaign_id ),
			[ 'status' => 404 ],
		);
	}

	/**
	 * Creates a conflict error response for closed campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign identifier from request payload.
	 */
	private function build_campaign_cannot_receive_donations_error( int $campaign_id ): WP_Error {

		return new WP_Error(
			'fundrik_campaign_cannot_receive_donations',
			sprintf( 'Cannot create donation for campaign "%d": campaign cannot receive donations.', $campaign_id ),
			[ 'status' => 409 ],
		);
	}

	/**
	 * Creates a conflict error response when a donation ID is reused for different payload.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationRestRequestData $payload Normalized request payload.
	 */
	private function build_donation_request_conflict_error( CreateDonationRestRequestData $payload ): WP_Error {

		return new WP_Error(
			'fundrik_donation_request_conflict',
			sprintf(
				'Cannot create donation "%s": request payload does not match existing donation.',
				$payload->donation_id->get_value(),
			),
			[ 'status' => 409 ],
		);
	}
}
