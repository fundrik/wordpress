<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Override;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Dispatches the WordPress 'rest_post_dispatch' filter to attached listeners.
 *
 * Validates the filter input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestPostDispatchFilterHookDispatcher implements HookDispatcherInterface {

	/**
	 * The WordPress hook name.
	 *
	 * @since 1.0.0
	 */
	private const string HOOK_NAME = 'rest_post_dispatch';

	/**
	 * The list of attached hook listeners.
	 *
	 * @var list<callable>
	 */
	private array $listeners = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param HookDispatcherLogger $logger Writes structured log entries for this hook.
	 */
	public function __construct(
		private readonly HookDispatcherLogger $logger,
	) {

		$this->logger->set_hook_name( self::HOOK_NAME );
		$this->logger->set_hook_dispatcher_class( self::class );
	}

	/**
	 * Attaches the given listener to the hook.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $listener Handles the hook dispatch.
	 */
	#[Override]
	public function attach( callable $listener ): void {

		$this->listeners[] = $listener;
	}

	/**
	 * Registers the WordPress filter callback.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_filter( self::HOOK_NAME, $this->handle( ... ), 10, 3 );
	}

	/**
	 * Handles the WordPress filter and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response Response object.
	 * @param mixed $server REST server instance.
	 * @param mixed $request Request object.
	 *
	 * @return mixed The filtered response or the original value if validation fails.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function handle( mixed $response, mixed $server, mixed $request ): mixed {

		try {

			$valid_response = $this->validate_response( $response );
			$valid_server = $this->validate_server( $server );
			$valid_request = $this->validate_request( $request );

			$result = $this->dispatch_to_listeners( $valid_response, $valid_server, $valid_request );

		} catch ( InvalidHookDispatcherArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			fundrik_set_failure_message( $e->getMessage() );
			return $response;

		} catch ( Throwable $e ) {

			// Listener exceptions must be logged in listener/BootUnit to avoid duplicate logs here.
			fundrik_set_failure_message( $e->getMessage() );
			return $response;
		}

		return $result;
	}

	/**
	 * Dispatches the validated hook arguments to attached listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Response $response The validated response.
	 * @param WP_REST_Server $server The validated REST server.
	 * @param WP_REST_Request $request The validated request.
	 *
	 * @return WP_REST_Response The value returned after listeners.
	 */
	private function dispatch_to_listeners(
		WP_REST_Response $response,
		WP_REST_Server $server,
		WP_REST_Request $request,
	): WP_REST_Response {

		$result = $response;

		foreach ( $this->listeners as $listener ) {

			$result = $listener( $result, $server, $request );

			$result = $this->validate_response( $result );
		}

		return $result;
	}

	/**
	 * Validates the 'response' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response The incoming response.
	 *
	 * @return WP_REST_Response The validated response.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_response( mixed $response ): WP_REST_Response {

		if ( ! $response instanceof WP_REST_Response ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'response', hook: self::HOOK_NAME );
		}

		return $response;
	}

	/**
	 * Validates the 'server' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $server The incoming REST server instance.
	 *
	 * @return WP_REST_Server The validated REST server.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_server( mixed $server ): WP_REST_Server {

		if ( ! $server instanceof WP_REST_Server ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'server', hook: self::HOOK_NAME );
		}

		return $server;
	}

	/**
	 * Validates the 'request' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $request The incoming request object.
	 *
	 * @return WP_REST_Request The validated request.
	 *
	 * @phpstan-return WP_REST_Request<array<string, mixed>>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_request( mixed $request ): WP_REST_Request {

		if ( ! $request instanceof WP_REST_Request ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'request', hook: self::HOOK_NAME );
		}

		return $request;
	}
}
