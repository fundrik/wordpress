<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Override;
use stdClass;
use Throwable;
use WP_Error;
use WP_REST_Request;

/**
 * Dispatches the WordPress 'rest_pre_insert_{post_type}' filter to attached listeners.
 *
 * Validates the filter input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestPreInsertCampaignFilterHookDispatcher implements HookDispatcherInterface {

	/**
	 * The resolved WordPress hook name.
	 *
	 * @since 1.0.0
	 */
	private string $hook_name;

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

		$this->hook_name = 'rest_pre_insert_' . CampaignPostTypeConfig::ID;

		$this->logger->set_hook_name( $this->hook_name );
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

		add_filter( $this->hook_name, $this->handle( ... ), 10, 2 );
	}

	/**
	 * Handles the WordPress filter and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $prepared_post An object representing a single post prepared for inserting or updating the database.
	 * @param mixed $request Request object.
	 *
	 * @return mixed The modified filtered post object, a WP_Error, or the original value if validation fails.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function handle( mixed $prepared_post, mixed $request ): mixed {

		try {

			$valid_post = $this->validate_prepared_post( $prepared_post );
			$valid_request = $this->validate_request( $request );

			$result = $this->dispatch_to_listeners( $valid_post, $valid_request );

		} catch ( InvalidHookDispatcherArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			fundrik_set_failure_message( $e->getMessage() );
			return $prepared_post;

		} catch ( Throwable $e ) {

			// Listener exceptions must be logged in listener/BootUnit to avoid duplicate logs here.
			fundrik_set_failure_message( $e->getMessage() );
			return $prepared_post;
		}

		return $result;
	}

	/**
	 * Dispatches the validated hook arguments to attached listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param stdClass $prepared_post The validated prepared post.
	 * @param WP_REST_Request $request The validated request.
	 *
	 * @return stdClass|WP_Error The value returned after listeners.
	 */
	private function dispatch_to_listeners( stdClass $prepared_post, WP_REST_Request $request ): stdClass|WP_Error {

		$result = $prepared_post;

		foreach ( $this->listeners as $listener ) {

			$result = $listener( $result, $request );

			$result = $this->validate_result( $result );
		}

		return $result;
	}

	/**
	 * Validates the 'prepared_post' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $prepared_post The post object from WordPress.
	 *
	 * @return stdClass The validated post.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_prepared_post( mixed $prepared_post ): stdClass {

		if ( ! $prepared_post instanceof stdClass ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'prepared_post', hook: $this->hook_name );
		}

		return $prepared_post;
	}

	/**
	 * Validates the 'request' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $request The incoming REST request.
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

	/**
	 * Validates the value returned by a listener.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $result The returned value.
	 *
	 * @return stdClass|WP_Error The validated result.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_result( mixed $result ): stdClass|WP_Error {

		if ( $result instanceof stdClass ) {
			return $result;
		}

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		throw InvalidHookDispatcherArgumentException::create( argument: 'returned', hook: $this->hook_name );
	}
}
