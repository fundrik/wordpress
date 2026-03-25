<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Override;
use Throwable;
use WP_Post;
use WP_REST_Request;

/**
 * Dispatches the WordPress 'rest_after_insert_{post_type}' action to attached listeners.
 *
 * Validates the action input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestAfterInsertCampaignActionHookDispatcher implements HookDispatcherInterface {

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

		$this->hook_name = 'rest_after_insert_' . CampaignPostTypeConfig::ID;

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
	 * Registers the WordPress action callback.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_action( $this->hook_name, $this->handle( ... ), 10, 3 );
	}

	/**
	 * Handles the WordPress action and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post The inserted or updated post object.
	 * @param mixed $request The REST request object.
	 * @param mixed $creating Whether WordPress created a new post.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function handle( mixed $post, mixed $request, mixed $creating ): void {

		try {

			$valid_post = $this->validate_post( $post );
			$valid_request = $this->validate_request( $request );
			$valid_creating = $this->validate_creating( $creating );

			$this->dispatch_to_listeners( $valid_post, $valid_request, $valid_creating );

		} catch ( InvalidHookDispatcherArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			fundrik_set_failure_message( $e->getMessage() );
			return;

		} catch ( Throwable $e ) {

			// Listener exceptions must be logged in listener/BootUnit to avoid duplicate logs here.
			fundrik_set_failure_message( $e->getMessage() );
			return;
		}
	}

	/**
	 * Dispatches the validated hook arguments to attached listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The validated post.
	 * @param WP_REST_Request $request The validated request.
	 * @param bool $creating The validated creating flag.
	 */
	private function dispatch_to_listeners( WP_Post $post, WP_REST_Request $request, bool $creating ): void {

		foreach ( $this->listeners as $listener ) {
			$listener( $post, $request, $creating );
		}
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
	 * Validates the 'creating' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $creating The incoming creating flag.
	 *
	 * @return bool True when creating a post, false when updating.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_creating( mixed $creating ): bool {

		if ( ! is_bool( $creating ) ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'creating', hook: $this->hook_name );
		}

		return $creating;
	}
}
