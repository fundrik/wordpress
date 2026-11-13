<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress REST request for tests.
 */
final class WpRestRequest {

	private string $body = '';

	/**
	 * Creates the REST request.
	 *
	 * @param string $method HTTP method.
	 * @param string $route REST route.
	 * @param array<string, mixed> $json_params Parsed JSON body parameters.
	 */
	public function __construct(
		private string $method = 'GET',
		private string $route = '',
		private array $json_params = [],
	) {}

	/**
	 * Returns the HTTP method.
	 */
	public function get_method(): string {

		return $this->method;
	}

	/**
	 * Returns the REST route.
	 */
	public function get_route(): string {

		return $this->route;
	}

	/**
	 * Returns parsed JSON body parameters.
	 *
	 * @return array<string, mixed> Parsed JSON body parameters.
	 */
	public function get_json_params(): array {

		return $this->json_params;
	}

	/**
	 * Returns the raw request body.
	 */
	public function get_body(): string {

		return $this->body;
	}

	/**
	 * Sets the raw request body.
	 */
	public function set_body( string $body ): void {

		$this->body = $body;
	}

	/**
	 * Returns a request parameter by name.
	 */
	public function get_param( string $key ): mixed {

		return $this->json_params[ $key ] ?? null;
	}

	/**
	 * Sets a request parameter by name.
	 */
	public function set_param( string $key, mixed $value ): void {

		$this->json_params[ $key ] = $value;
	}
}
