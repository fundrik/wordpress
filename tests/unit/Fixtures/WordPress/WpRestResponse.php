<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress REST response for tests.
 */
final class WpRestResponse {

	/**
	 * Creates the REST response.
	 *
	 * @param mixed $data Response data.
	 * @param int $status HTTP status code.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function __construct(
		private mixed $data = null,
		private int $status = 200,
	) {}

	/**
	 * Returns the response data.
	 *
	 * @return mixed Response data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function get_data(): mixed {

		return $this->data;
	}

	/**
	 * Replaces the response data.
	 *
	 * @param mixed $data Response data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function set_data( mixed $data ): void {

		$this->data = $data;
	}

	/**
	 * Returns the HTTP status code.
	 */
	public function get_status(): int {

		return $this->status;
	}
}
