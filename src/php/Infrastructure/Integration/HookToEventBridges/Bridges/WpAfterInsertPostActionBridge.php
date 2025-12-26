<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\PostSavedEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Throwable;
use WP_Post;

/**
 * Bridges the WordPress 'wp_after_insert_post' action to internal integration events.
 *
 * Validates the action input before dispatching an internal event.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WpAfterInsertPostActionBridge implements HookToEventBridgeInterface {

	private const HOOK_NAME = 'wp_after_insert_post';

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
	 * Registers the 'wp_after_insert_post' WordPress action and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_action( self::HOOK_NAME, $this->handle( ... ), 10, 4 );

		$this->logger->log_registered();
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Handles the 'wp_after_insert_post' action logic.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_id Post ID.
	 * @param mixed $post Post object.
	 * @param mixed $update Whether this is an existing post being updated.
	 * @param mixed $post_before Null for new posts, the WP_Post object prior to the update for updated posts.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function handle( mixed $post_id, mixed $post, mixed $update, mixed $post_before ): void {

		try {
			$valid_post_id = $this->validate_post_id( $post_id );
			$valid_post = $this->validate_post( $post );
			$valid_update = $this->validate_update( $update );
			$valid_post_before = $this->validate_post_before( $post_before );

			$this->dispatcher->dispatch(
				new PostSavedEvent(
					post_id: $valid_post_id,
					post: $valid_post,
					update: $valid_update,
					post_before: $valid_post_before,
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

		$this->log_handled(
			'dispatched',
			post_id: $valid_post_id,
			post_type: $valid_post->post_type,
			update: $valid_update,
			post_before_present: $valid_post_before instanceof WP_Post,
		);
	}
	// phpcs:enable

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
	 * Validates the 'update' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $update The incoming update flag.
	 *
	 * @return bool The validated update flag.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_update( mixed $update ): bool {

		if ( ! is_bool( $update ) ) {
			throw InvalidBridgeArgumentException::create( argument: 'update', hook: self::HOOK_NAME );
		}

		return $update;
	}

	/**
	 * Validates the 'post_before' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post_before The incoming pre-update post object.
	 *
	 * @return WP_Post|null The validated object or null.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_post_before( mixed $post_before ): ?WP_Post {

		if ( $post_before !== null && ! $post_before instanceof WP_Post ) {
			throw InvalidBridgeArgumentException::create( argument: 'post_before', hook: self::HOOK_NAME );
		}

		return $post_before;
	}

	/**
	 * Builds and delegates the final handle log entry to the logger.
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The action bridge outcome.
	 * @param int $post_id The post ID.
	 * @param string|null $post_type The post type, if available.
	 * @param bool $update Whether this was an update.
	 * @param bool $post_before_present Whether a pre-update object was provided.
	 */
	private function log_handled(
		string $outcome,
		int $post_id,
		?string $post_type,
		bool $update,
		bool $post_before_present,
	): void {

		$this->logger->log_handled(
			$outcome,
			[
				'post_id' => $post_id,
				'post_type' => $post_type,
				'update' => $update,
				'post_before_present' => $post_before_present,
			],
		);
	}
}
