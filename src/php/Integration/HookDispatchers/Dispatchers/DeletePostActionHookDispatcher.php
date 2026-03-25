<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Override;
use Throwable;
use WP_Post;

/**
 * Dispatches the WordPress 'delete_post' action to attached listeners.
 *
 * Validates the action input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class DeletePostActionHookDispatcher implements HookDispatcherInterface {

	private const string HOOK_NAME = 'delete_post';

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
	 * Registers the WordPress action callback.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_action( self::HOOK_NAME, $this->handle( ... ), 10, 2 );
	}

	/**
	 * Handles the WordPress action and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id The deleted post ID.
	 * @param mixed $post The deleted post object.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function handle( mixed $post_id, mixed $post ): void {

		try {

			$valid_post_id = $this->validate_post_id( $post_id );
			$valid_post = $this->validate_post( $post );

			$this->dispatch_to_listeners( $valid_post_id, $valid_post );

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
	 * @param int $post_id The validated post ID.
	 * @param WP_Post $post The validated post.
	 */
	private function dispatch_to_listeners( int $post_id, WP_Post $post ): void {

		foreach ( $this->listeners as $listener ) {
			$listener( $post_id, $post );
		}
	}

	/**
	 * Validates the 'post_id' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id The incoming post ID.
	 *
	 * @return int The validated post ID.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_post_id( mixed $post_id ): int {

		if ( ! is_int( $post_id ) ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'post_id', hook: self::HOOK_NAME );
		}

		return $post_id;
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
			throw InvalidHookDispatcherArgumentException::create( argument: 'post', hook: self::HOOK_NAME );
		}

		return $post;
	}
}
