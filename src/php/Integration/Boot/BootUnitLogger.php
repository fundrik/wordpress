<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use LogicException;
use Psr\Log\LoggerInterface;

/**
 * Writes structured log entries for integration boot units.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class BootUnitLogger {

	/**
	 * The fully qualified boot unit class name.
	 *
	 * @since 1.0.0
	 */
	private string $boot_unit_class;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for boot units operations.
	 */
	public function __construct(
		private LoggerInterface $logger,
	) {}

	/**
	 * Sets the boot unit class for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the boot unit.
	 */
	public function set_boot_unit_class( string $class_name ): void {

		$this->boot_unit_class = $class_name;
	}

	/**
	 * Logs that the boot unit has been started (debug).
	 *
	 * @param string $operation_name The operation name.
	 *
	 * @since 1.0.0
	 */
	public function log_started( string $operation_name ): void {

		$this->assert_class_name_is_set();

		$this->logger->debug(
			sprintf( '%s started.', $operation_name ),
			$this->logger_context(),
		);
	}

	/**
	 * Logs a debug entry with the boot unit context (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $extra Additional context entries to merge.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_debug( string $message, array $extra = [] ): void {

		$this->assert_class_name_is_set();

		$this->logger->debug(
			$message,
			$this->logger_context( $extra ),
		);
	}

	/**
	 * Logs that the boot unit has been completed (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $operation_name The operation name.
	 * @param array<string, mixed> $extra Extra context entries describing additional unit data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_completed( string $operation_name, array $extra = [] ): void {

		$this->assert_class_name_is_set();

		$this->logger->debug(
			sprintf( '%s completed.', $operation_name ),
			$this->logger_context( $extra ),
		);
	}

	/**
	 * Builds the structured logger context for the hook dispatcher.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $extra Additional context entries to merge.
	 *
	 * @return array<string, mixed> The structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( array $extra = [] ): array {

		return [
			'service_class' => $this->boot_unit_class,
			'logger_class' => self::class,
			'component' => 'boot_units',
			'layer' => 'integration',
			'system' => 'wordpress',
		] + $extra;
	}

	/**
	 * Ensures that boot unit class is configured before logging.
	 *
	 * @since 1.0.0
	 *
	 * @throws LogicException When called before class name is set.
	 */
	private function assert_class_name_is_set(): void {

		if ( ! isset( $this->boot_unit_class ) ) {

			throw new LogicException(
				'BootUnitLogger class name is not set. Call set_boot_unit_class() before logging.',
			);
		}
	}
}
