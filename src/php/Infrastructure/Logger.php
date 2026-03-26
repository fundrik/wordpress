<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure;

use LogicException;
use Psr\Log\LoggerInterface;

/**
 * Provides the structured logging base for integration services.
 *
 * @since 1.0.0
 *
 * @internal
 */
abstract class Logger {

	/**
	 * The structured log system name.
	 *
	 * @since 1.0.0
	 */
	private const string SYSTEM = 'wordpress';

	/**
	 * The fully qualified service class name.
	 *
	 * @since 1.0.0
	 */
	private string $service_class;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries.
	 * @param string $component Structured log component name.
	 * @param string $layer Structured log layer name.
	 */
	public function __construct(
		private LoggerInterface $logger,
		private string $component,
		private string $layer,
	) {
	}

	/**
	 * Sets the service class for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param class-string $class_name Fully qualified service class name.
	 */
	public function set_service_class( string $class_name ): void {

		$this->service_class = $class_name;
	}

	/**
	 * Logs a debug entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $extra Additional context entries.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_debug( string $message, array $extra = [] ): void {

		$this->assert_context_is_set();

		$this->logger->debug(
			$message,
			$this->logger_context( $extra ),
		);
	}

	/**
	 * Logs an info entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $extra Additional context entries.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_info( string $message, array $extra = [] ): void {

		$this->assert_context_is_set();

		$this->logger->info(
			$message,
			$this->logger_context( $extra ),
		);
	}

	/**
	 * Logs a warning entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $extra Additional context entries.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_warning( string $message, array $extra = [] ): void {

		$this->assert_context_is_set();

		$this->logger->warning(
			$message,
			$this->logger_context( $extra ),
		);
	}

	/**
	 * Logs an error entry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $extra Additional context entries.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_error( string $message, array $extra = [] ): void {

		$this->assert_context_is_set();

		$this->logger->error(
			$message,
			$this->logger_context( $extra ),
		);
	}

	/**
	 * Builds the structured logger context.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $extra Additional context entries.
	 *
	 * @return array<string, mixed> The structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( array $extra = [] ): array {

		return [
			'service_class' => $this->service_class,
			'logger_class' => static::class,
			'component' => $this->component,
			'layer' => $this->layer,
			'system' => self::SYSTEM,
		] + $this->base_context() + $extra;
	}

	/**
	 * Builds the base structured logger context.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The base structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	protected function base_context(): array {

		return [];
	}

	/**
	 * Ensures that required context is configured before logging.
	 *
	 * @since 1.0.0
	 */
	protected function assert_context_is_set(): void {

		$this->assert_service_class_is_set();
	}

	/**
	 * Ensures that service class is configured before logging.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Error message when service class is missing.
	 *
	 * @throws LogicException When called before service class is set.
	 */
	protected function assert_service_class_is_set(
		string $message = 'Service class must be set before logging. Given: unset.',
	): void {

		if ( ! isset( $this->service_class ) ) {
			throw new LogicException( $message );
		}
	}
}
