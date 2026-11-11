<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress error for tests.
 */
final readonly class WpError {

	/**
	 * Creates the error.
	 *
	 * @param string|int $code Error code.
	 * @param string $message Error message.
	 * @param mixed $data Error data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function __construct(
		private string|int $code = '',
		private string $message = '',
		private mixed $data = null,
	) {}

	/**
	 * Returns the error code.
	 */
	public function get_error_code(): string|int {

		return $this->code;
	}

	/**
	 * Returns the error message.
	 */
	public function get_error_message(): string {

		return $this->message;
	}

	/**
	 * Returns the error data.
	 *
	 * @return mixed Error data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function get_error_data(): mixed {

		return $this->data;
	}
}
