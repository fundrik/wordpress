<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterRestPrepareCampaignEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Throwable;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Bridges the WordPress 'rest_prepare_(post_type)' filter to internal integration events for campaigns.
 *
 * Validates the filter input before dispatching an internal event.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestPrepareCampaignFilterBridge implements HookToEventBridgeInterface {

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
		private readonly WordPressContextInterface $context,
		private readonly InfrastructureEventDispatcherInterface $dispatcher,
		private readonly PostTypeIdReader $post_type_id_reader,
		private readonly BridgeLogger $logger,
	) {

		$this->logger->set_bridge_class( self::class );
	}

	/**
	 * Registers the 'rest_prepare_(post_type)' WordPress filter and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		$this->post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );

		// The hook name is dynamic so set the logger's hook name only after the post type is known.
		$this->logger->set_hook_name( $this->get_hook_name() );

		add_filter( $this->get_hook_name(), $this->handle( ... ), 10, 3 );

		$this->logger->log_registered();
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Handles the 'rest_prepare_(post_type)' filter logic for campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response Response object.
	 * @param mixed $post Post object.
	 * @param mixed $request Request object.
	 *
	 * @return mixed The filtered response.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function handle( mixed $response, mixed $post, mixed $request ): mixed {

		try {
			$valid_response = $this->validate_response( $response );
			$valid_post = $this->validate_post( $post );
			$valid_request = $this->validate_request( $request );

			$event = new FilterRestPrepareCampaignEvent(
				response: $valid_response,
				post: $valid_post,
				request: $valid_request,
				context: $this->context,
			);

			$this->dispatcher->dispatch( $event );

		} catch ( InvalidBridgeArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			return $response;

		} catch ( Throwable $e ) {

			$this->logger->log_dispatch_failed( $e );
			throw $e;
		}

		$changed = $event->response !== $valid_response;

		$this->log_handled( outcome: $changed ? 'changed' : 'unchanged' );

		return $event->response;
	}
	// phpcs:enable

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
			throw InvalidBridgeArgumentException::create( argument: 'response', hook: $this->get_hook_name() );
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
			throw InvalidBridgeArgumentException::create( argument: 'post', hook: $this->get_hook_name() );
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
			throw InvalidBridgeArgumentException::create( argument: 'request', hook: $this->get_hook_name() );
		}

		return $request;
	}

	/**
	 * Returns the dynamic name of the REST prepare hook for the campaign post type.
	 *
	 * @since 1.0.0
	 *
	 * @return string The name of the WordPress hook to bridge.
	 */
	private function get_hook_name(): string {

		return 'rest_prepare_' . $this->post_type;
	}

	/**
	 * Builds and delegates the final handle log entry to the logger (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome Whether listeners modified the value.
	 * @param array<string, mixed> $extra Additional context entries to merge.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_handled( string $outcome, array $extra = [] ): void {

		$this->logger->log_handled( $outcome, $extra );
	}
}
