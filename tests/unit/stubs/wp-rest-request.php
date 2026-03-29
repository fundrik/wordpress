<?php

declare(strict_types=1);

if ( class_exists( 'WP_REST_Request' ) ) {
	return;
}

/**
 * Minimal WP_REST_Request stub for unit tests.
 *
 * @internal
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, SlevomatCodingStandard.Files.TypeNameMatchesFileName.NoMatchBetweenTypeNameAndFileName, FundrikStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class WP_REST_Request {

	private array $json_params = [];
	private array $attributes = [];
	private array $default_params = [];
	private string $method;
	private string $route;

	public function __construct( string $method = 'GET', string $route = '' ) {

		$this->method = $method;
		$this->route = $route;
	}

	public function get_method(): string {

		return $this->method;
	}

	public function get_route(): string {

		return $this->route;
	}

	public function set_json_params( array $json_params ): void {

		$this->json_params = $json_params;
	}

	public function get_json_params(): array {

		return $this->json_params;
	}

	public function set_param( string $key, mixed $value ): void {

		$this->json_params[ $key ] = $value;
	}

	public function get_param( string $key ): mixed {

		if ( array_key_exists( $key, $this->json_params ) ) {
			return $this->json_params[ $key ];
		}

		return $this->default_params[ $key ] ?? null;
	}

	public function set_attributes( array $attributes ): void {

		$this->attributes = $attributes;
	}

	public function set_default_params( array $default_params ): void {

		$this->default_params = $default_params;
	}

	public function has_valid_params(): true|WP_Error {

		$args = $this->attributes['args'] ?? [];
		$required = [];

		foreach ( $args as $key => $arg ) {

			if ( ( $arg['required'] ?? false ) !== true || $this->get_param( $key ) !== null ) {
				continue;
			}

			$required[] = $key;
		}

		if ( $required !== [] ) {
			return new WP_Error(
				'rest_missing_callback_param',
				sprintf( 'Missing parameter(s): %s', implode( ', ', $required ) ),
				[ 'status' => 400 ],
			);
		}

		foreach ( $args as $key => $arg ) {
			$value = $this->get_param( $key );

			if ( $value === null ) {
				continue;
			}

			$validation_error = $this->validate_arg( $key, $value, $arg );

			if ( $validation_error instanceof WP_Error ) {
				return $validation_error;
			}
		}

		return true;
	}

	public function sanitize_params(): true|WP_Error {

		$args = $this->attributes['args'] ?? [];

		foreach ( $args as $key => $arg ) {
			$value = $this->get_param( $key );

			if ( $value === null ) {
				continue;
			}

			if ( ( $arg['sanitize_callback'] ?? null ) === 'rest_sanitize_request_arg' ) {
				$this->json_params[ $key ] = $this->sanitize_value_from_schema( $value, $arg );
				continue;
			}

			if ( isset( $arg['sanitize_callback'] ) && is_callable( $arg['sanitize_callback'] ) ) {
				$sanitized_value = call_user_func( $arg['sanitize_callback'], $value, $this, $key );

				if ( $sanitized_value instanceof WP_Error ) {
					return new WP_Error(
						'rest_invalid_param',
						sprintf( 'Invalid parameter(s): %s', $key ),
						[ 'status' => 400 ],
					);
				}

				$this->json_params[ $key ] = $sanitized_value;
				continue;
			}

			if ( ( $arg['type'] ?? null ) === 'integer' ) {
				$this->json_params[ $key ] = (int) $value;
				continue;
			}

			if ( ( $arg['type'] ?? null ) !== 'string' ) {
				continue;
			}

			$this->json_params[ $key ] = (string) $value;
		}

		return true;
	}

	/**
	 * Validates a single registered request argument.
	 *
	 * @param array<string, mixed> $arg
	 */
	private function validate_arg( string $key, mixed $value, array $arg ): ?WP_Error {

		if ( ( $arg['validate_callback'] ?? null ) === 'rest_validate_request_arg' ) {
			return $this->validate_value_from_schema( $key, $value, $arg );
		}

		if ( isset( $arg['validate_callback'] ) && is_callable( $arg['validate_callback'] ) ) {
			$result = call_user_func( $arg['validate_callback'], $value, $this, $key );

			if ( $result instanceof WP_Error || $result === false ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf( 'Invalid parameter(s): %s', $key ),
					[ 'status' => 400 ],
				);
			}

			return null;
		}

		return $this->validate_value_from_schema( $key, $value, $arg );
	}

	/**
	 * Validates a request value against a minimal JSON schema subset used in tests.
	 *
	 * @param array<string, mixed> $arg
	 */
	private function validate_value_from_schema( string $key, mixed $value, array $arg ): ?WP_Error {

		if ( ( $arg['type'] ?? null ) === 'integer' ) {

			if ( ! is_numeric( $value ) || (float) $value !== round( (float) $value ) ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf( 'Invalid parameter(s): %s', $key ),
					[ 'status' => 400 ],
				);
			}

			if ( isset( $arg['minimum'] ) && (float) $value < (float) $arg['minimum'] ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf( 'Invalid parameter(s): %s', $key ),
					[ 'status' => 400 ],
				);
			}

			return null;
		}

		if ( ( $arg['type'] ?? null ) === 'string' ) {

			if ( ! is_string( $value ) ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf( 'Invalid parameter(s): %s', $key ),
					[ 'status' => 400 ],
				);
			}

			if (
				( $arg['format'] ?? null ) === 'uuid'
				&& ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value )
			) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf( 'Invalid parameter(s): %s', $key ),
					[ 'status' => 400 ],
				);
			}
		}

		return null;
	}

	/**
	 * Sanitizes a request value against a minimal JSON schema subset used in tests.
	 *
	 * @param array<string, mixed> $arg
	 */
	private function sanitize_value_from_schema( mixed $value, array $arg ): mixed {

		if ( ( $arg['type'] ?? null ) === 'integer' ) {
			return (int) $value;
		}

		if ( ( $arg['type'] ?? null ) === 'string' ) {
			return (string) $value;
		}

		return $value;
	}
}
