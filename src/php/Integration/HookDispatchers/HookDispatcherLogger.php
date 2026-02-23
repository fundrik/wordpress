<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use LogicException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes structured log entries for WordPress hook dispatchers.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class HookDispatcherLogger {

	/**
	 * The WordPress hook name.
	 *
	 * @since 1.0.0
	 */
	private string $hook_name;

	/**
	 * The fully qualified hook dispatcher class name.
	 *
	 * @since 1.0.0
	 */
	private string $hook_dispatcher_class;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for hook dispatcher operations.
	 */
	public function __construct(
		private LoggerInterface $logger,
	) {}

	/**
	 * Sets the WordPress hook name for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_name The WordPress hook name handled by the hook dispatcher.
	 */
	public function set_hook_name( string $hook_name ): void {

		$this->hook_name = $hook_name;
	}

	/**
	 * Sets the hook dispatcher class for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the hook dispatcher.
	 */
	public function set_hook_dispatcher_class( string $class_name ): void {

		$this->hook_dispatcher_class = $class_name;
	}

	/**
	 * Logs that the hook dispatcher has been registered in WordPress (debug).
	 *
	 * @since 1.0.0
	 */
	public function log_registered(): void {

		$this->assert_context_is_set();

		$this->logger->debug(
			'Hook dispatcher registered.',
			$this->logger_context(
				[
					'operation' => 'register',
					'outcome' => 'registered',
				],
			),
		);
	}

	/**
	 * Logs that the input arguments failed validation and the hook call is invalid (warning).
	 *
	 * @since 1.0.0
	 *
	 * @param InvalidHookDispatcherArgumentException $e The validation exception raised by the hook dispatcher.
	 */
	public function log_invalid_input( InvalidHookDispatcherArgumentException $e ): void {

		$this->assert_context_is_set();

		$this->logger->warning(
			$e->getMessage(),
			$this->logger_context(
				[
					'operation' => 'validate',
					'outcome' => 'invalid',
					'invalid_argument' => $e->argument,
					'invoked' => false,
				],
			),
		);
	}

	/**
	 * Logs that dispatch failed due to an exception in listeners (error).
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $e The thrown exception from the dispatch stage.
	 */
	public function log_dispatch_failed( Throwable $e ): void {

		$this->assert_context_is_set();

		$this->logger->error(
			sprintf( "Hook dispatcher dispatch failed for hook '%s'.", $this->hook_name ),
			$this->logger_context(
				[
					'operation' => 'dispatch',
					'outcome' => 'error',
					'invoked' => true,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs the final outcome of handling the hook call (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The result status, such as 'handled' or 'skipped'.
	 * @param array<string, mixed> $extra Extra context entries describing additional hook data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_handled( string $outcome, array $extra = [] ): void {

		$this->assert_context_is_set();

		$this->logger->debug(
			'Hook dispatcher handled.',
			$this->logger_context(
				[
					'operation' => 'handle',
					'outcome' => $outcome,
					'invoked' => true,
				] + $extra,
			),
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
			'service_class' => $this->hook_dispatcher_class,
			'logger_class' => self::class,
			'component' => 'hook_dispatchers',
			'hook_name' => $this->hook_name,
			'layer' => 'integration',
			'system' => 'wordpress',
		] + $extra;
	}

	/**
	 * Ensures that context (hook and dispatcher class) is configured before logging.
	 *
	 * @since 1.0.0
	 *
	 * @throws LogicException When called before context is set.
	 */
	private function assert_context_is_set(): void {

		if ( ! isset( $this->hook_name, $this->hook_dispatcher_class ) ) {

			throw new LogicException(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'HookDispatcherLogger context is not set. Call set_hook_name() and set_hook_dispatcher_class() before logging.',
			);
		}
	}
}
