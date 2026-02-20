<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Hooks\Filters;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\Hooks\HookInterface;
use Fundrik\WordPress\Integration\Hooks\HookLogger;
use Fundrik\WordPress\Integration\Hooks\InvalidHookArgumentException;
use InvalidArgumentException;
use Throwable;
use WP_Block_Editor_Context;

/**
 * Dispatches the WordPress 'allowed_block_types_all' filter to attached listeners.
 *
 * Validates the filter input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class AllowedBlockTypesAllFilterHook implements HookInterface {

	private const string HOOK_NAME = 'allowed_block_types_all';

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
	 * @param HookLogger $logger Writes structured log entries for this hook.
	 */
	public function __construct(
		private readonly HookLogger $logger,
	) {

		$this->logger->set_hook_name( self::HOOK_NAME );
		$this->logger->set_hook_class( self::class );
	}

	/**
	 * Attaches the given listener to the hook.
	 *
	 * @since 1.0.0
	 *
	 * @param callable $listener Handles the hook dispatch.
	 */
	public function attach( callable $listener ): void {

		$this->listeners[] = $listener;
	}

	/**
	 * Registers the WordPress filter callback.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_filter( self::HOOK_NAME, $this->handle( ... ), 10, 2 );

		$this->logger->log_registered();
	}

	/**
	 * Handles the WordPress filter and dispatches it to listeners.
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

			$result = $this->dispatch_to_listeners( $valid_allowed, $valid_context );

		} catch ( InvalidHookArgumentException $e ) {

			$this->logger->log_invalid_input( $e );

			return $allowed;

		} catch ( Throwable $e ) {

			$this->logger->log_dispatch_failed( $e );

			throw $e;
		}

		$changed = $result !== $valid_allowed;

		$this->log_handled( outcome: $changed ? 'changed' : 'unchanged', returned: $result );

		return $result;
	}

	/**
	 * Dispatches the validated hook arguments to attached listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string>|bool $allowed The validated and normalized allowed block types.
	 * @param WP_Block_Editor_Context $editor_context The validated editor context.
	 *
	 * @return array<string>|bool The value returned after listeners.
	 */
	private function dispatch_to_listeners( array|bool $allowed, WP_Block_Editor_Context $editor_context ): array|bool {

		$result = $allowed;

		foreach ( $this->listeners as $listener ) {

			$result = $listener( $result, $editor_context );

			$result = $this->validate_allowed( $result );
		}

		return $result;
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
				throw InvalidHookArgumentException::create( argument: 'allowed', hook: self::HOOK_NAME );
			}
		}

		if ( $allowed !== true && $allowed !== false ) {
			throw InvalidHookArgumentException::create( argument: 'allowed', hook: self::HOOK_NAME );
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
			throw InvalidHookArgumentException::create( argument: 'editor_context', hook: self::HOOK_NAME );
		}

		return $editor_context;
	}

	/**
	 * Builds and delegates the final handle log entry to the logger (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The hook outcome.
	 * @param mixed $returned The value returned back to WordPress after listeners.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function log_handled( string $outcome, mixed $returned ): void {

		$this->logger->log_handled(
			$outcome,
			[
				'listener_count' => count( $this->listeners ),
				'returned_type' => is_bool( $returned ) ? 'bool' : 'array',
				'returned_count' => is_array( $returned ) ? count( $returned ) : null,
			],
		);
	}
}
