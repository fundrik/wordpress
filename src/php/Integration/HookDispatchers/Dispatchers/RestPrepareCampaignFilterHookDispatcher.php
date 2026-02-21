<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Throwable;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Dispatches the WordPress 'rest_prepare_{post_type}' filter to attached listeners.
 *
 * Validates the filter input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestPrepareCampaignFilterHookDispatcher implements HookDispatcherInterface {

	/**
	 * The resolved WordPress hook name.
	 *
	 * @since 1.0.0
	 */
	private string $hook_name;

	/**
	 * The list of attached hook listeners.
	 *
	 * @var array<int, callable>
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

		$this->hook_name = 'rest_prepare_' . CampaignPostTypeConfig::ID;

		$this->logger->set_hook_name( $this->hook_name );
		$this->logger->set_hook_class( self::class );
	}

	/**
	 * Attaches the given listener to the hook.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $listener Handles the hook dispatch.
	 */
	public function attach( callable $listener ): void {

		$this->listeners[] = $listener;
	}

	/**
	 * Registers the WordPress filter callback.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_filter( $this->hook_name, $this->handle( ... ), 10, 3 );

		$this->logger->log_registered();
	}

	/**
	 * Handles the WordPress filter and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response Response object.
	 * @param mixed $post Post object.
	 * @param mixed $request Request object.
	 *
	 * @return mixed The filtered response or the original value if validation fails.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function handle( mixed $response, mixed $post, mixed $request ): mixed {

		try {

			$valid_response = $this->validate_response( $response );
			$valid_post = $this->validate_post( $post );
			$valid_request = $this->validate_request( $request );

			$result = $this->dispatch_to_listeners( $valid_response, $valid_post, $valid_request );

		} catch ( InvalidHookDispatcherArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			return $response;

		} catch ( Throwable $e ) {

			$this->logger->log_dispatch_failed( $e );
			throw $e;
		}

		$changed = $result !== $valid_response;

		$this->logger->log_handled(
			$changed ? 'changed' : 'unchanged',
			[
				'listener_count' => count( $this->listeners ),
			],
		);

		return $result;
	}

	/**
	 * Dispatches the validated hook arguments to attached listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Response $response The validated response.
	 * @param WP_Post $post The validated post.
	 * @param WP_REST_Request $request The validated request.
	 *
	 * @return WP_REST_Response The value returned after listeners.
	 */
	private function dispatch_to_listeners(
		WP_REST_Response $response,
		WP_Post $post,
		WP_REST_Request $request,
	): WP_REST_Response {

		$result = $response;

		foreach ( $this->listeners as $listener ) {

			$result = $listener( $result, $post, $request );

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
			throw InvalidHookDispatcherArgumentException::create( argument: 'response', hook: $this->hook_name );
		}

		return $response;
	}

	/**
	 * Validates the 'post' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post The incoming post object.
	 *
	 * @return WP_Post The validated post.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_post( mixed $post ): WP_Post {

		if ( ! $post instanceof WP_Post ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'post', hook: $this->hook_name );
		}

		return $post;
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
			throw InvalidHookDispatcherArgumentException::create( argument: 'request', hook: $this->hook_name );
		}

		return $request;
	}
}
