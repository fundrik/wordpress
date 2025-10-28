<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterBeforeRestInsertCampaignEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextFactory;
use stdClass;
use Throwable;
use WP_REST_Request;

/**
 * Bridges the WordPress 'rest_pre_insert_fundrik_campaign' filter to internal integration events.
 *
 * Validates the filter input before dispatching an internal event.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestPreInsertCampaignFilterBridge implements HookToEventBridgeInterface {

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
	 * @param WordPressContextFactory $context_factory Creates WordPressContext instances on demand.
	 * @param EventDispatcherInterface $dispatcher Dispatches the bridged events.
	 * @param PostTypeIdReader $post_type_id_reader Resolves the post type ID for CampaignPostType.
	 * @param BridgeLogger $logger Writes structured log entries for this hook bridge.
	 */
	public function __construct(
		private readonly WordPressContextFactory $context_factory,
		private readonly EventDispatcherInterface $dispatcher,
		private readonly PostTypeIdReader $post_type_id_reader,
		private readonly BridgeLogger $logger,
	) {

		$this->logger->set_hook_name( $this->get_hook_name() );
		$this->logger->set_bridge_class( self::class );
	}

	/**
	 * Registers the 'rest_pre_insert_(post_type)' WordPress filter and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		$this->post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );

		add_filter( $this->get_hook_name(), $this->handle( ... ), 10, 2 );

		$this->logger->log_registered();
	}

	/**
	 * Handles the 'rest_pre_insert_(post_type)' filter logic for campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $prepared_post An object representing a single post prepared for inserting or updating the database.
	 * @param mixed $request Request object.
	 *
	 * @return mixed The modified filtered post object or the original value if validation fails.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function handle( mixed $prepared_post, mixed $request ): mixed {

		try {
			$valid_post = $this->validate_prepared_post( $prepared_post );
			$valid_request = $this->validate_request( $request );

			$event = new FilterBeforeRestInsertCampaignEvent(
				prepared_post: $valid_post,
				request: $valid_request,
				context: $this->context_factory->create(),
			);

			$this->dispatcher->dispatch( $event );

		} catch ( InvalidBridgeArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			return $prepared_post;

		} catch ( Throwable $e ) {

			$this->logger->log_dispatch_failed( $e );
			throw $e;
		}

		$changed = $event->prepared_post !== $valid_post;

		$this->log_handled( outcome: $changed ? 'changed' : 'unchanged', returned: $event->prepared_post );

		return $event->prepared_post;
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
			throw InvalidBridgeArgumentException::create( argument: 'prepared_post', hook: $this->get_hook_name() );
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
			throw InvalidBridgeArgumentException::create( argument: 'request', hook: $this->get_hook_name() );
		}

		return $request;
	}

	/**
	 * Returns the dynamic name of the REST pre-insert hook for the campaign post type.
	 *
	 * @since 1.0.0
	 *
	 * @return string The name of the WordPress hook to bridge.
	 */
	private function get_hook_name(): string {

		return 'rest_pre_insert_' . $this->post_type;
	}

	/**
	 * Builds and delegates the final handle log entry to the logger.
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome Whether listeners modified the value.
	 * @param mixed $returned The value returned back to WordPress after listeners.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_handled( string $outcome, mixed $returned ): void {

		$this->logger->log_handled(
			$outcome,
			[
				'returned_type' => is_object( $returned ) ? get_debug_type( $returned ) : gettype( $returned ),
				'returned_props' => is_object( $returned ) ? count( get_object_vars( $returned ) ) : null,
			],
		);
	}
}
