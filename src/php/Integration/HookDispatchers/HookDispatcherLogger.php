<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use Fundrik\WordPress\Infrastructure\Logger;
use LogicException;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Writes structured log entries for WordPress hook dispatchers.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class HookDispatcherLogger extends Logger {

	/**
	 * The WordPress hook name.
	 *
	 * @since 1.0.0
	 */
	private string $hook_name;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LoggerInterface $logger Writes structured log entries for hook dispatcher operations.
	 */
	public function __construct( LoggerInterface $logger ) {

		parent::__construct( $logger, 'hook_dispatchers', 'integration' );
	}

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

		$this->set_service_class( $class_name );
	}

	/**
	 * Logs that the input arguments failed validation and the hook call is invalid (error).
	 *
	 * @since 1.0.0
	 *
	 * @param InvalidHookDispatcherArgumentException $e The validation exception raised by the hook dispatcher.
	 */
	public function log_invalid_input( InvalidHookDispatcherArgumentException $e ): void {

		$this->log_error(
			$e->getMessage(),
			[
				'operation' => 'validate',
				'outcome' => 'invalid',
				'invalid_argument' => $e->argument,
				'invoked' => false,
			],
		);
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
	#[Override]
	protected function base_context(): array {

		return [
			'hook_name' => $this->hook_name,
		];
	}

	/**
	 * Ensures that context (hook and dispatcher class) is configured before logging.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	protected function assert_context_is_set(): void {

		if ( ! isset( $this->hook_name ) ) {
			throw new LogicException( 'Hook dispatcher logger context must be set before logging. Given: unset.' );
		}

		$this->assert_service_class_is_set(
			'Hook dispatcher logger context must be set before logging. Given: unset.',
		);
	}
}
