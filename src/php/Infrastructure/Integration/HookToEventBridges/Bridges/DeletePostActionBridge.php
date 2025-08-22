<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\PostDeletedEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextFactory;
use Psr\Log\LoggerInterface;
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
	 * @param WordPressContextFactory $context_factory Creates WordPressContext instances on demand.
	 * @param EventDispatcherInterface $dispatcher Dispatches the bridged events.
	 * @param LoggerInterface $logger Logs validation errors and bridging-related warnings.
	 */
	public function __construct(
		private WordPressContextFactory $context_factory,
		private EventDispatcherInterface $dispatcher,
		private LoggerInterface $logger,
	) {}

	/**
	 * Registers the 'delete_post' WordPress action and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_action(
			self::HOOK_NAME,
			$this->handle( ... ),
			10,
			2,
		);

		$this->log_registered();
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
					context: $this->context_factory->create(),
				),
			);

		} catch ( InvalidBridgeArgumentException $e ) {

			$this->log_invalid_input( $e );
			return;

		} catch ( Throwable $e ) {

			$this->log_dispatch_failed( $e );
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
	 * Logs that the hook bridge has been registered in WordPress.
	 *
	 * @since 1.0.0
	 */
	private function log_registered(): void {

		$this->logger->debug( 'Hook bridge registered.', $this->logger_context() );
	}

	/**
	 * Logs that the input arguments failed validation and the bridge call is invalid.
	 *
	 * @since 1.0.0
	 *
	 * @param InvalidBridgeArgumentException $e The validation exception raised by the bridge.
	 */
	private function log_invalid_input( InvalidBridgeArgumentException $e ): void {

		$this->logger->warning(
			$e->getMessage(),
			$this->logger_context(
				[
					'stage' => 'validate',
					'outcome' => 'invalid',
					'invalid_argument' => $e->argument,
					'invoked' => false,
				],
			),
		);
	}

	/**
	 * Logs that the dispatch stage failed due to an exception in listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $e The thrown exception from the dispatch stage.
	 */
	private function log_dispatch_failed( Throwable $e ): void {

		$this->logger->error(
			sprintf( "Bridge dispatch failed for hook '%s'.", self::HOOK_NAME ),
			$this->logger_context(
				[
					'stage' => 'dispatch',
					'outcome' => 'error',
					'invoked' => true,
					'exception' => $e,
				],
			),
		);
	}

	/**
	 * Logs the final outcome of handling the hook bridge call.
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The action bridge outcome.
	 * @param int $post_id The deleted post ID.
	 * @param string|null $post_type The deleted post type, if available.
	 */
	private function log_handled( string $outcome, int $post_id, ?string $post_type ): void {

		$this->logger->debug(
			'Hook bridge handled.',
			$this->logger_context(
				[
					'outcome' => $outcome,
					'invoked' => true,
					'post_id' => $post_id,
					'post_type' => $post_type,
				],
			),
		);
	}

	/**
	 * Builds the structured logger context for this hook bridge.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $extra Additional context entries to merge.
	 *
	 * @return array<string, mixed> The structured context payload.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function logger_context( array $extra = [] ): array {

		return [
			'system' => 'hook_bridge',
			'wordpress_hook_name' => self::HOOK_NAME,
			'hook_bridge_class' => self::class,
		] + $extra;
	}
}
