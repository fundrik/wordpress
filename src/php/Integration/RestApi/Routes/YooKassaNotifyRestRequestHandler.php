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
use YooKassa\Model\Notification\NotificationInterface;
use YooKassa\Model\Payment\PaymentInterface;
use YooKassa\Model\Refund\Refund;

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

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
	/**
	 * Handles incoming notification requests.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 *
	 * @return WP_REST_Response|WP_Error Notification response or error details.
	 *
	 * @todo Resolve notifications by persisted gateway payment ID and add optional YooKassa API verification.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response|WP_Error {

		$payload = $this->get_payload( $request );

		if ( $payload instanceof WP_Error ) {
			return $payload;
		}

		if ( ! $this->verify_notification_ip( $request, $payload ) ) {
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

		$event = $notification->getEvent();
		$result_type = $this->map_yookassa_event_to_donation_payment_result_type( $event );

		if ( $result_type === null ) {
			return new WP_REST_Response( [ 'status' => 'ignored' ], 200 );
		}

		$notification_object = $this->get_notification_object( $notification );

		if ( $notification_object === null ) {
			return new WP_REST_Response( [ 'status' => 'ignored' ], 200 );
		}

		try {
			$result = new DonationPaymentResult(
				$this->get_donation_id( $notification_object )->to_entity_id(),
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
	 * Returns the decoded notification payload.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 *
	 * @return array<string, mixed>|WP_Error Decoded payload or validation error.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function get_payload( WP_REST_Request $request ): array|WP_Error {

		$payload = json_decode( $request->get_body(), true );

		if ( ! is_array( $payload ) ) {
			return new WP_Error(
				'fundrik_yookassa_invalid_notification_payload',
				'YooKassa notification payload must decode to an array. Given: invalid payload.',
				[ 'status' => 400 ],
			);
		}

		return $payload;
	}

	/**
	 * Converts a YooKassa event to a donation payment result type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $event YooKassa notification event.
	 *
	 * @return DonationPaymentResultType|null Result type, if supported.
	 */
	private function map_yookassa_event_to_donation_payment_result_type( string $event ): ?DonationPaymentResultType {

		return [
			NotificationEventType::PAYMENT_SUCCEEDED => DonationPaymentResultType::Succeeded,
			NotificationEventType::PAYMENT_CANCELED => DonationPaymentResultType::Rejected,
			NotificationEventType::REFUND_SUCCEEDED => DonationPaymentResultType::Refunded,
		][ $event ] ?? null;
	}

	/**
	 * Returns the supported object from a YooKassa notification.
	 *
	 * @since 1.0.0
	 *
	 * @param NotificationInterface $notification YooKassa notification.
	 *
	 * @return PaymentInterface|Refund|null Notification object, if supported.
	 */
	private function get_notification_object( NotificationInterface $notification ): PaymentInterface|Refund|null {

		$notification_object = $notification->getObject();

		if ( ! $notification_object instanceof PaymentInterface && ! $notification_object instanceof Refund ) {
			return null;
		}

		return $notification_object;
	}

	/**
	 * Returns the donation ID from YooKassa metadata.
	 *
	 * @since 1.0.0
	 *
	 * @param PaymentInterface|Refund $notification_object YooKassa notification object.
	 *
	 * @return DonationId Donation ID.
	 */
	private function get_donation_id( PaymentInterface|Refund $notification_object ): DonationId {

		return DonationId::from_value(
			$notification_object->getMetadata()?->toArray()['donation_id'] ?? '',
		);
	}

	/**
	 * Checks whether the request originated from a YooKassa IP address.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Incoming REST request.
	 * @param array<string, mixed> $payload Decoded notification payload.
	 *
	 * @return bool True when the request IP is verified.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function verify_notification_ip( WP_REST_Request $request, array $payload ): bool {

		// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$remote_address = wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' );

		try {
			$verified_by_ip = $this->client->isNotificationIPTrusted( $remote_address );
		} catch ( Throwable ) {
			$verified_by_ip = false;
		}

		/**
		 * Filters whether a YooKassa notification is verified by source IP address.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $verified_by_ip Whether the notification IP address is verified.
		 * @param WP_REST_Request $request Incoming REST request.
		 * @param array<string, mixed> $payload Decoded notification payload.
		 *
		 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
		 */
		return (bool) apply_filters(
			'fundrik_yookassa_notification_verified_by_ip',
			$verified_by_ip,
			$request,
			$payload,
		);
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
