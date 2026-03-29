<?php

declare(strict_types=1);

if ( class_exists( 'WP_REST_Response' ) ) {
	return;
}

/**
 * Minimal WP_REST_Response stub for unit tests.
 *
 * @internal
 */
class WP_REST_Response {

	/**
	 * @var mixed
	 */
	private mixed $data;

	private int $status;

	/**
	 * @param mixed $data
	 */
	public function __construct( mixed $data = null, int $status = 200 ) {

		$this->data = $data;
		$this->status = $status;
	}

	/**
	 * @return mixed
	 */
	public function get_data(): mixed {

		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	public function set_data( mixed $data ): void {

		$this->data = $data;
	}

	public function get_status(): int {

		return $this->status;
	}

	public function set_status( int $status ): void {

		$this->status = $status;
	}
}
