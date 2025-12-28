<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use InvalidArgumentException;
use Throwable;
use WP_Block_Editor_Context;

/**
 * Bridges the WordPress 'allowed_block_types_all' filter to internal integration events.
 *
 * Validates the filter input before dispatching an internal event.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class AllowedBlockTypesAllFilterBridge implements HookToEventBridgeInterface {

	private const HOOK_NAME = 'allowed_block_types_all';

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
	 * Registers the 'allowed_block_types_all' WordPress filter and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_filter( self::HOOK_NAME, $this->handle( ... ), 10, 2 );

		$this->logger->log_registered();
	}

	/**
	 * Handles the 'allowed_block_types_all' filter logic.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $allowed The list of allowed block type slugs, or a boolean to allow or disallow all.
	 * @param mixed $editor_context The current block editor context.
	 *
	 * @return mixed The modified list of allowed blocks or the original value if validation fails.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function handle( mixed $allowed, mixed $editor_context ): mixed {

		try {

			$valid_allowed = $this->validate_allowed( $allowed );
			$valid_context = $this->validate_editor_context( $editor_context );

			$event = new FilterAllowedBlockTypesEvent( $valid_allowed, $valid_context, $this->context );

			$this->dispatcher->dispatch( $event );

		} catch ( InvalidBridgeArgumentException $e ) {

			$this->logger->log_invalid_input( $e );

			return $allowed;

		} catch ( Throwable $e ) {

			$this->logger->log_dispatch_failed( $e );

			throw $e;
		}

		$changed = $event->allowed !== $valid_allowed;

		$this->log_handled( outcome: $changed ? 'changed' : 'unchanged', returned: $event->allowed );

		return $event->allowed;
	}

	/**
	 * Validates and normalizes the 'allowed' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $allowed The original value passed by WordPress.
	 *
	 * @return array<string>|bool The validated and normalized allowed block types.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_allowed( mixed $allowed ): array|bool {

		if ( is_array( $allowed ) ) {

			try {
				return array_map( TypeCaster::to_string( ... ), $allowed );
			} catch ( InvalidArgumentException ) {
				throw InvalidBridgeArgumentException::create( argument: 'allowed', hook: self::HOOK_NAME );
			}
		}

		if ( $allowed !== true && $allowed !== false ) {
			throw InvalidBridgeArgumentException::create( argument: 'allowed', hook: self::HOOK_NAME );
		}

		return $allowed;
	}

	/**
	 * Validates the 'editor_context' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $editor_context The context passed by WordPress.
	 *
	 * @return WP_Block_Editor_Context The validated context.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_editor_context( mixed $editor_context ): WP_Block_Editor_Context {

		if ( ! $editor_context instanceof WP_Block_Editor_Context ) {
			throw InvalidBridgeArgumentException::create( argument: 'editor_context', hook: self::HOOK_NAME );
		}

		return $editor_context;
	}

	/**
	 * Builds and delegates the final handle log entry to the logger (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The action bridge outcome.
	 * @param mixed $returned The value returned back to WordPress after listeners.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_handled( string $outcome, mixed $returned ): void {

		$this->logger->log_handled(
			$outcome,
			[
				'returned_type' => is_bool( $returned ) ? 'bool' : 'array',
				'returned_count' => is_array( $returned ) ? count( $returned ) : null,
			],
		);
	}
}
