<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookToEventBridges;

use InvalidArgumentException;

/**
 * Thrown when a bridged WordPress hook receives an argument with an invalid type or structure.
 *
 * @since 1.0.0
 */
final class InvalidBridgeArgumentException extends InvalidArgumentException {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $argument The invalid argument name (without `$`).
	 * @param string $hook The hook name where the error occurred.
	 * @param string|null $message Custom message to override the default format.
	 */
	public function __construct(
		public readonly string $argument,
		public readonly string $hook,
		?string $message = null,
	) {

		$msg = $message ?? "Invalid \${$this->argument} argument in '{$this->hook}' hook.";

		parent::__construct( $msg );
	}

	/**
	 * Creates the exception for a specific argument within a hook.
	 *
	 * @since 1.0.0
	 *
	 * @param string $argument The invalid argument name (without `$`).
	 * @param string $hook The hook name where the error occurred.
	 *
	 * @return self Describes the invalid hook argument.
	 */
	public static function create( string $argument, string $hook ): self {

		return new self( $argument, $hook );
	}
}
