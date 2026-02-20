<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Hooks;

use InvalidArgumentException;

/**
 * Thrown when a WordPress hook receives an argument with an invalid type or structure.
 *
 * @since 1.0.0
 */
final class InvalidHookArgumentException extends InvalidArgumentException {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $argument The invalid argument name (without `$`).
	 * @param string $hook The hook name where the error occurred.
	 * @param string|null $message Overrides the default exception message.
	 */
	public function __construct(
		public readonly string $argument,
		public readonly string $hook,
		?string $message = null,
	) {

		parent::__construct(
			$message ?? sprintf(
				'Invalid %s argument in %s hook.',
				'$' . $this->argument,
				$this->hook,
			),
		);
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
