<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use Fundrik\WordPress\Infrastructure\Logger;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Writes structured log entries for integration boot units.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class BootUnitLogger extends Logger {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for boot units operations.
	 */
	public function __construct(
		LoggerInterface $logger,
	) {

		parent::__construct( $logger, 'boot_units', 'integration' );
	}

	/**
	 * Sets the boot unit class for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the boot unit.
	 */
	public function set_boot_unit_class( string $class_name ): void {

		$this->set_service_class( $class_name );
	}

	/**
	 * Ensures that boot unit class is configured before logging.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	protected function assert_context_is_set(): void {

		$this->assert_service_class_is_set( 'Boot unit class must be set before logging. Given: unset.' );
	}
}
