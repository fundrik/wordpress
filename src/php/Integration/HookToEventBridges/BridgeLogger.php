<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookToEventBridges;

use LogicException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Writes structured log entries for WordPress hook bridges.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class BridgeLogger {

	/**
	 * The bridged WordPress hook name.
	 *
	 * @since 1.0.0
	 */
	private string $hook_name;

	/**
	 * The fully qualified bridge class name.
	 *
	 * @since 1.0.0
	 */
	private string $bridge_class;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for hook bridge operations.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
	) {}

	/**
	 * Sets the WordPress hook name for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_name The WordPress hook name handled by this bridge.
	 */
	public function set_hook_name( string $hook_name ): void {

		$this->hook_name = $hook_name;
	}

	/**
	 * Sets the bridge class for subsequent log entries.
	 *
	 * @since 1.0.0
	 *
	 * @param string $bridge_class The fully qualified class name of the hook bridge.
	 */
	public function set_bridge_class( string $bridge_class ): void {

		$this->bridge_class = $bridge_class;
	}

	/**
	 * Logs that the hook bridge has been registered in WordPress (debug).
	 *
	 * @since 1.0.0
	 */
	public function log_registered(): void {

		$this->assert_context_is_set();

		$this->logger->debug(
			'Hook bridge registered.',
			$this->logger_context(
				[
					'operation' => 'register_hook_bridge',
					'outcome' => 'registered',
				],
			),
		);
	}

	/**
	 * Logs that the input arguments failed validation and the bridge call is invalid (warning).
	 *
	 * @since 1.0.0
	 *
	 * @param InvalidBridgeArgumentException $e The validation exception raised by the bridge.
	 */
	public function log_invalid_input( InvalidBridgeArgumentException $e ): void {

		$this->assert_context_is_set();

		$this->logger->warning(
			$e->getMessage(),
			$this->logger_context(
				[
					'operation' => 'validate_hook_bridge',
					'outcome' => 'invalid',
					'invalid_argument' => $e->argument,
					'invoked' => false,
				],
			),
		);
	}

	/**
	 * Logs that the dispatch stage failed due to an exception in listeners (error).
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $e The thrown exception from the dispatch stage.
	 */
	public function log_dispatch_failed( Throwable $e ): void {

		$this->assert_context_is_set();

		$this->logger->error(
			sprintf( "Bridge dispatch failed for hook '%s'.", $this->hook_name ),
			$this->logger_context(
				[
					'operation' => 'dispatch_hook_bridge',
					'outcome' => 'error',
					'invoked' => true,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs the final outcome of handling the hook bridge call (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The result status, such as 'handled' or 'skipped'.
	 * @param array<string, mixed> $extra Extra context entries describing additional bridge data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function log_handled( string $outcome, array $extra = [] ): void {

		$this->assert_context_is_set();

		$this->logger->debug(
			'Hook bridge handled.',
			$this->logger_context(
				[
					'operation' => 'handle_hook_bridge',
					'outcome' => $outcome,
					'invoked' => true,
				] + $extra,
			),
		);
	}

	/**
	 * Builds the structured logger context for the hook bridge.
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
			'logger_class' => self::class,
			'component' => 'hook_bridges',
			'layer' => 'infrastructure',
			'system' => 'wordpress',
			'hook_name' => $this->hook_name,
			'bridge_class' => $this->bridge_class,
		] + $extra;
	}

	/**
	 * Ensures that context (hook and class) is configured before logging.
	 *
	 * @since 1.0.0
	 *
	 * @throws LogicException When called before context is set.
	 */
	private function assert_context_is_set(): void {

		if ( ! isset( $this->hook_name, $this->bridge_class ) ) {

			throw new LogicException(
				'BridgeLogger context is not set. Call set_hook_name() and set_bridge_class() before logging.',
			);
		}
	}
}
