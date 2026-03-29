<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi;

use Fundrik\WordPress\Infrastructure\Logger;
use Override;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes structured log entries for REST route handlers.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestRouteHandlerLogger extends Logger {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for REST route handler operations.
	 */
	public function __construct(
		LoggerInterface $logger,
	) {

		parent::__construct( $logger, 'rest_route_handlers', 'integration' );
	}

	/**
	 * Sets the REST route handler class for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the REST route handler.
	 */
	public function set_rest_route_handler_class( string $class_name ): void {

		$this->set_service_class( $class_name );
	}

	/**
	 * Logs an invalid create-donation request payload (warning).
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $exception Original payload normalization exception.
	 */
	public function log_invalid_create_donation_request( Throwable $exception ): void {

		$this->log_warning(
			'Create-donation REST request payload is invalid.',
			[
				'operation' => 'normalize_request',
				'outcome' => 'invalid',
				'exception' => $exception,
			],
		);
	}

	/**
	 * Logs a create-donation request failure (error).
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign identifier from request payload.
	 * @param int $amount_minor Donation amount in minor units.
	 * @param Throwable $exception Original create-donation exception.
	 */
	public function log_create_donation_failed( int $campaign_id, int $amount_minor, Throwable $exception ): void {

		$this->log_failed_operation(
			'Creating donation from REST request failed.',
			'create_donation',
			[
				'campaign_id' => $campaign_id,
				'amount_minor' => $amount_minor,
				'exception' => $exception,
			],
		);
	}

	/**
	 * Logs a failed REST route handler operation (error).
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Log message.
	 * @param string $operation Failed operation name.
	 * @param array<string, mixed> $extra Additional context entries.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_failed_operation( string $message, string $operation, array $extra = [] ): void {

		$this->log_error(
			$message,
			[
				'operation' => $operation,
				'outcome' => 'failed',
			] + $extra,
		);
	}

	/**
	 * Ensures that REST route handler class is configured before logging.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	protected function assert_context_is_set(): void {

		$this->assert_service_class_is_set( 'REST route handler class must be set before logging. Given: unset.' );
	}
}
