<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

/**
 * Represents extracted REST validation failure data.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestValidationFailure {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $error_code REST error code.
	 * @param string|null $error_message REST error message, if present.
	 * @param list<string> $invalid_params Invalid parameter names.
	 */
	public function __construct(
		public string $error_code,
		public ?string $error_message,
		public array $invalid_params,
	) {}
}
