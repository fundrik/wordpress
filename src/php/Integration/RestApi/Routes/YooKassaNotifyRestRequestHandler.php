<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResult;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\DonationPaymentResultType;
use Fundrik\Core\Components\Donations\Application\UseCases\ProcessDonationPaymentResult\ProcessDonationPaymentResultHandler;
use Fundrik\WordPress\Components\Donations\Domain\DonationId;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use YooKassa\Client;
use YooKassa\Model\Notification\NotificationEventType;
use YooKassa\Model\Notification\NotificationFactory;

/**
 * Handles YooKassa notification requests.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class YooKassaNotifyRestRequestHandler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Client $client Checks YooKassa notification IP addresses.
	 * @param RestRouteHandlerLogger $logger Writes structured log entries for REST route handler operations.
	 * @param ProcessDonationPaymentResultHandler $process_payment_result Processes normalized donation payment results.
	 */
	public function __construct(
		private Client $client,
		private RestRouteHandlerLogger $logger,
		private ProcessDonationPaymentResultHandler $process_payment_result,
	) {

		$this->logger->set_rest_route_handler_class( self::class );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Handles incoming notification requests.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 *
	 * @return WP_REST_Response|WP_Error Notification response or error details.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$payload = json_decode( $request->get_body(), true );
		$verified = apply_filters(
			'fundrik_yookassa_notification_verified',
			$this->verify_notification_ip(),
			$request,
			$payload,
		);

		if ( ! $verified ) {
			return new WP_Error(
				'fundrik_notification_verification_failed',
				'YooKassa notification verification failed.',
				[ 'status' => 403 ],
			);
		}

		try {
			$notification = ( new NotificationFactory() )->factory( $payload );
		} catch ( Throwable ) {
			return new WP_REST_Response( [ 'status' => 'ignored' ], 200 );
		}

		try {
			$event = $notification->getEvent();

			$result_type = [
				NotificationEventType::PAYMENT_SUCCEEDED => DonationPaymentResultType::Succeeded,
				NotificationEventType::PAYMENT_CANCELED => DonationPaymentResultType::Rejected,
				NotificationEventType::REFUND_SUCCEEDED => DonationPaymentResultType::Refunded,
			][ $event ] ?? null;

			if ( $result_type === null ) {
				return new WP_REST_Response( [ 'status' => 'ignored' ], 200 );
			}

			$notification_object = $notification->getObject();
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			$donation_id = DonationId::from_value( $notification_object->getMetadata()?->toArray()['donation_id'] ?? '' );

			$result = new DonationPaymentResult(
				$donation_id->to_entity_id(),
				$result_type,
			);

			$this->process_payment_result->handle( $result );
		} catch ( Throwable $e ) {
			$this->log_processing_failed( $e );

			return new WP_Error( 'fundrik_notify_failed', $e->getMessage(), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [ 'status' => 'processed' ], 200 );
	}
	// phpcs:enable

	/**
	 * Checks whether the request originated from a YooKassa IP address.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the request IP belongs to YooKassa.
	 */
	private function verify_notification_ip(): bool {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$remote_address = wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' );

		try {
			return $this->client->isNotificationIPTrusted( $remote_address );
		} catch ( Throwable ) {
			return false;
		}
	}

	/**
	 * Logs a failed YooKassa notification processing attempt (error).
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $exception Original notification processing exception.
	 */
	private function log_processing_failed( Throwable $exception ): void {

		$this->logger->log_error(
			'YooKassa notification processing failed.',
			[
				'operation' => 'process_notification',
				'outcome' => 'failed',
				'exception' => $exception,
			],
		);
	}
}
