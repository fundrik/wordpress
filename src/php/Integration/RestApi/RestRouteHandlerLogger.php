<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi;

use Fundrik\WordPress\Infrastructure\Logger;
use Override;
use Psr\Log\LoggerInterface;

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
	 * Ensures that REST route handler class is configured before logging.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	protected function assert_context_is_set(): void {

		$this->assert_service_class_is_set( 'REST route handler class must be set before logging. Given: unset.' );
	}
}
