<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterBeforeRestInsertCampaignEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\CampaignPostType;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextFactory;
use Psr\Log\LoggerInterface;
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
	 * @param LoggerInterface $logger Logs registration, validation, and dispatch outcomes.
	 */
	public function __construct(
		private readonly WordPressContextFactory $context_factory,
		private readonly EventDispatcherInterface $dispatcher,
		private readonly PostTypeIdReader $post_type_id_reader,
		private readonly LoggerInterface $logger,
	) {}

	/**
	 * Registers the 'rest_pre_insert_(post_type)' WordPress filter and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		$this->post_type = $this->post_type_id_reader->get_id( CampaignPostType::class );

		add_filter(
			$this->get_hook_name(),
			$this->handle( ... ),
			10,
			2,
		);

		$this->log_registered();
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

			$this->log_invalid_input( $e );
			return $prepared_post;

		} catch ( Throwable $e ) {

			$this->log_dispatch_failed( $e );
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
			sprintf( "Bridge dispatch failed for hook '%s'.", $this->get_hook_name() ),
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
	 * @param 'changed'|'unchanged' $outcome Whether listeners modified the value.
	 * @param mixed $returned The value returned back to WordPress after listeners.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_handled( string $outcome, mixed $returned ): void {

		$this->logger->debug(
			'Hook bridge handled.',
			$this->logger_context(
				[
					'outcome' => $outcome,
					'invoked' => true,
					'returned_type' => is_object( $returned ) ? get_debug_type( $returned ) : gettype( $returned ),
					'returned_props' => is_object( $returned ) ? count( get_object_vars( $returned ) ) : null,
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
			'wordpress_hook_name' => $this->get_hook_name(),
			'hook_bridge_class' => self::class,
		] + $extra;
	}
}
