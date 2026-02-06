<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\ActionCampaignSavedViaRestEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Throwable;
use WP_Post;
use WP_REST_Request;

/**
 * Bridges the WordPress 'rest_after_insert_{post_type}' action to internal integration events for campaigns.
 *
 * Validates the action input before dispatching an internal event.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestAfterInsertCampaignActionBridge implements HookToEventBridgeInterface {

	/**
	 * The post type id for the campaign post type.
	 *
	 * @since 1.0.0
	 */
	private string $post_type;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WordPressContextInterface $context Provides the WordPress-specific plugin context.
	 * @param InfrastructureEventDispatcherInterface $dispatcher Dispatches the bridged events.
	 * @param PostTypeIdReader $post_type_id_reader Resolves the post type ID for CampaignPostType.
	 * @param BridgeLogger $logger Writes structured log entries for this hook bridge.
	 */
	public function __construct(
		private WordPressContextInterface $context,
		private InfrastructureEventDispatcherInterface $dispatcher,
		private PostTypeIdReader $post_type_id_reader,
		private BridgeLogger $logger,
	) {

		$this->logger->set_bridge_class( self::class );
	}

	/**
	 * Registers the 'rest_after_insert_{post_type}' WordPress action and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		$this->post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );

		// The hook name is dynamic so set the logger's hook name only after the post type is known.
		$this->logger->set_hook_name( $this->get_hook_name() );

		add_action( $this->get_hook_name(), $this->handle( ... ), 10, 3 );

		$this->logger->log_registered();
	}

	/**
	 * Handles the 'rest_after_insert_{post_type}' action logic for campaigns.
	 *
	 * Validates the input, dispatches the event, and logs the outcome.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post The inserted or updated post object.
	 * @param mixed $request The REST request object.
	 * @param mixed $creating Whether WordPress created a new post.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function handle( mixed $post, mixed $request, mixed $creating ): void {

		try {

			$valid_post = $this->validate_post( $post );
			$valid_request = $this->validate_request( $request );
			$valid_creating = $this->validate_creating( $creating );

			$this->dispatcher->dispatch(
				new ActionCampaignSavedViaRestEvent(
					post: $valid_post,
					request: $valid_request,
					creating: $valid_creating,
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
			outcome: 'dispatched',
			extra: [
				'post_id' => $valid_post->ID,
				'post_type' => $valid_post->post_type,
				'creating' => $valid_creating,
				'method' => $valid_request->get_method(),
			],
		);
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
			throw InvalidBridgeArgumentException::create( argument: 'post', hook: $this->get_hook_name() );
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
			throw InvalidBridgeArgumentException::create( argument: 'request', hook: $this->get_hook_name() );
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
			throw InvalidBridgeArgumentException::create( argument: 'creating', hook: $this->get_hook_name() );
		}

		return $creating;
	}

	/**
	 * Returns the dynamic name of the REST after-insert hook for the campaign post type.
	 *
	 * @since 1.0.0
	 *
	 * @return string The name of the WordPress hook to bridge.
	 */
	private function get_hook_name(): string {

		return 'rest_after_insert_' . $this->post_type;
	}

	/**
	 * Builds and delegates the final handle log entry to the logger (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The action bridge outcome.
	 * @param array<string, mixed> $extra Additional context entries to merge.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_handled( string $outcome, array $extra = [] ): void {

		$this->logger->log_handled( $outcome, $extra );
	}
}
