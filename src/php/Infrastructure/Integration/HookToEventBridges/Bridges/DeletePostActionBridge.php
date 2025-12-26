<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\PostDeletedEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Throwable;
use WP_Post;

/**
 * Bridges the WordPress 'delete_post' action to internal integration events.
 *
 * Validates the action input before dispatching an internal event.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DeletePostActionBridge implements HookToEventBridgeInterface {

	private const HOOK_NAME = 'delete_post';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WordPressContextInterface $context Provides the WordPress-specific plugin context.
	 * @param InfrastructureEventDispatcherInterface $dispatcher Dispatches the bridged events.
	 * @param BridgeLogger $logger Writes structured log entries for this hook bridge.
	 */
	public function __construct(
		private WordPressContextInterface $context,
		private InfrastructureEventDispatcherInterface $dispatcher,
		private BridgeLogger $logger,
	) {

		$this->logger->set_hook_name( self::HOOK_NAME );
		$this->logger->set_bridge_class( self::class );
	}

	/**
	 * Registers the 'delete_post' WordPress action and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_action( self::HOOK_NAME, $this->handle( ... ), 10, 2 );

		$this->logger->log_registered();
	}

	/**
	 * Handles the 'delete_post' action logic.
	 *
	 * Validate the input, dispatch the event, and log the outcome.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id Post ID.
	 * @param mixed $post Post object.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function handle( mixed $post_id, mixed $post ): void {

		try {
			$valid_post_id = $this->validate_post_id( $post_id );
			$valid_post = $this->validate_post( $post );

			$this->dispatcher->dispatch(
				new PostDeletedEvent(
					post_id: $valid_post_id,
					post: $valid_post,
					context: $this->context,
				),
			);

		} catch ( InvalidBridgeArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			return;

		} catch ( Throwable $e ) {

			$this->logger->log_dispatch_failed( $e );
			throw $e;
		}

		$this->log_handled( outcome: 'dispatched', post_id: $valid_post_id, post_type: $valid_post->post_type );
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
			throw InvalidBridgeArgumentException::create( argument: 'post_id', hook: self::HOOK_NAME );
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
			throw InvalidBridgeArgumentException::create( argument: 'post', hook: self::HOOK_NAME );
		}

		return $post;
	}

	/**
	 * Builds and delegates the final handle log entry to the logger.
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The action bridge outcome.
	 * @param int $post_id The deleted post ID.
	 * @param string|null $post_type The deleted post type, if available.
	 */
	private function log_handled( string $outcome, int $post_id, ?string $post_type ): void {

		$this->logger->log_handled(
			$outcome,
			[
				'post_id' => $post_id,
				'post_type' => $post_type,
			],
		);
	}
}
