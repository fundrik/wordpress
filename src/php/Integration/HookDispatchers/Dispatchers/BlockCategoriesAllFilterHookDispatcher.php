<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Override;
use Throwable;
use WP_Block_Editor_Context;

/**
 * Dispatches the WordPress 'block_categories_all' filter to attached listeners.
 *
 * Validates the filter input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class BlockCategoriesAllFilterHookDispatcher implements HookDispatcherInterface {

	private const string HOOK_NAME = 'block_categories_all';

	/**
	 * The list of attached hook listeners.
	 *
	 * @var list<callable>
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
	 * Registers the WordPress filter callback.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_filter( self::HOOK_NAME, $this->handle( ... ), 10, 2 );
	}

	/**
	 * Handles the WordPress filter and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $categories The list of block categories.
	 * @param mixed $editor_context The current block editor context.
	 *
	 * @return mixed The modified list of block categories or the original value if validation fails.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function handle( mixed $categories, mixed $editor_context ): mixed {

		try {

			$valid_categories = $this->validate_categories( $categories );
			$valid_context = $this->validate_editor_context( $editor_context );

			$result = $this->dispatch_to_listeners( $valid_categories, $valid_context );

		} catch ( InvalidHookDispatcherArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			fundrik_set_failure_message( $e->getMessage() );
			return $categories;

		} catch ( Throwable $e ) {

			fundrik_set_failure_message( $e->getMessage() );
			return $categories;
		}

		return $result;
	}

	/**
	 * Dispatches the validated hook arguments to attached listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $categories The validated block categories.
	 * @param WP_Block_Editor_Context $editor_context The validated editor context.
	 *
	 * @return array<int, array<string, mixed>> The value returned after listeners.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function dispatch_to_listeners( array $categories, WP_Block_Editor_Context $editor_context ): array {

		$result = $categories;

		foreach ( $this->listeners as $listener ) {
			$result = $listener( $result, $editor_context );
			$result = $this->validate_categories( $result );
		}

		return $result;
	}

	/**
	 * Validates the 'categories' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $categories The original value passed by WordPress.
	 *
	 * @return array<int, array<string, mixed>> The validated block categories.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_categories( mixed $categories ): array {

		if ( ! is_array( $categories ) ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'categories', hook: self::HOOK_NAME );
		}

		foreach ( $categories as $category ) {

			if ( ! is_array( $category ) ) {
				throw InvalidHookDispatcherArgumentException::create( argument: 'categories', hook: self::HOOK_NAME );
			}
		}

		return $categories;
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
			throw InvalidHookDispatcherArgumentException::create( argument: 'editor_context', hook: self::HOOK_NAME );
		}

		return $editor_context;
	}
}
