<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPostDispatchFilterHookDispatcher;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Override;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Logs transport-level validation failures for create-donation REST requests.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class LogCreateDonationRestRequestFailuresBootUnit implements BootUnitInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param RestPostDispatchFilterHookDispatcher $rest_post_dispatch_hook Observes REST responses after dispatch.
	 * @param BootUnitLogger $logger Writes structured boot-unit logs.
	 */
	public function __construct(
		private RestPostDispatchFilterHookDispatcher $rest_post_dispatch_hook,
		private BootUnitLogger $logger,
	) {

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches create-donation request failure logging to REST response dispatch.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->rest_post_dispatch_hook->attach( $this->log_validation_failure( ... ) );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Logs a schema-level validation failure for the create-donation route.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Response $response REST response.
	 * @param WP_REST_Server $server REST server.
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response Unchanged response.
	 *
	 * @phpstan-return WP_REST_Response<array<string, mixed>>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	private function log_validation_failure(
		WP_REST_Response $response,
		WP_REST_Server $server,
		WP_REST_Request $request,
	): WP_REST_Response {

		if ( ! $this->should_log( $request, $response ) ) {
			return $response;
		}

		$response_data = $response->get_data();
		$data = $this->extract_response_data( $response_data );

		if ( $data === null ) {
			$this->log_response_data_extraction_failure( $request, $response, $response_data );
			return $response;
		}

		if ( ! $this->is_validation_error_code( $data->error_code ) ) {
			return $response;
		}

		$this->logger->log_warning(
			'Create-donation REST request validation failed.',
			[
				'route' => $request->get_route(),
				'method' => $request->get_method(),
				'status' => $response->get_status(),
				'error_code' => $data->error_code,
				'error_message' => $data->error_message,
				'invalid_params' => $data->invalid_params,
			],
		);

		return $response;
	}
	// phpcs:enable

	/**
	 * Returns whether the response should be logged.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @param WP_REST_Response $response REST response.
	 *
	 * @return bool True when the response should be logged.
	 */
	private function should_log( WP_REST_Request $request, WP_REST_Response $response ): bool {

		return $request->get_method() === WP_REST_Server::CREATABLE
			&& $request->get_route() === RestRouteDefinitions::get_request_path( DonationsRestRoute::class )
			&& $response->get_status() === 400;
	}

	/**
	 * Extracts REST validation failure response data for logging.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response_data REST response data.
	 *
	 * @return RestValidationFailure|null Loggable response data, null otherwise.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function extract_response_data( mixed $response_data ): ?RestValidationFailure {

		if ( ! is_array( $response_data ) ) {
			return null;
		}

		$error_code = $response_data['code'] ?? null;

		if ( ! is_string( $error_code ) ) {
			return null;
		}

		$error_message = $response_data['message'] ?? null;

		return new RestValidationFailure(
			$error_code,
			is_string( $error_message ) ? $error_message : null,
			$this->extract_invalid_params( $response_data ),
		);
	}

	/**
	 * Logs REST response data extraction failure.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @param WP_REST_Response $response REST response.
	 * @param mixed $response_data REST response data.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_response_data_extraction_failure(
		WP_REST_Request $request,
		WP_REST_Response $response,
		mixed $response_data,
	): void {

		$context = [
			'route' => $request->get_route(),
			'method' => $request->get_method(),
			'status' => $response->get_status(),
			'response_data_type' => get_debug_type( $response_data ),
		];

		if ( is_array( $response_data ) ) {
			$context['response_data_keys'] = array_values(
				array_filter(
					array_keys( $response_data ),
					is_string( ... ),
				),
			);
		}

		$this->logger->log_warning( 'Create-donation REST failure response data could not be extracted.', $context );
	}

	/**
	 * Returns whether the REST error code is a schema validation failure.
	 *
	 * @since 1.0.0
	 *
	 * @param string $error_code REST error code.
	 *
	 * @return bool True when the error code represents a schema validation failure.
	 */
	private function is_validation_error_code( string $error_code ): bool {

		return in_array( $error_code, [ 'rest_invalid_param', 'rest_missing_callback_param' ], true );
	}

	/**
	 * Returns invalid parameter names from REST error response data.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $response_data REST error response payload.
	 *
	 * @return list<string> Invalid parameter names.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function extract_invalid_params( array $response_data ): array {

		$error_data = $response_data['data'] ?? null;

		if ( ! is_array( $error_data ) ) {
			return [];
		}

		$params = $error_data['params'] ?? null;

		if ( ! is_array( $params ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_keys( $params ),
				is_string( ... ),
			),
		);
	}
}
