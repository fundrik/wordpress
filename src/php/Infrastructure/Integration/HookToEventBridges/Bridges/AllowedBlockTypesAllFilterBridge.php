<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\Core\Support\TypeCaster;
use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\InvalidBridgeArgumentException;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
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
	 * Registers the 'allowed_block_types_all' WordPress filter and bridge it to the internal events.
	 *
	 * Validates the hook arguments and dispatches an event if they are valid; otherwise, skips processing.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_filter(
			self::HOOK_NAME,
			$this->handle( ... ),
			10,
			2,
		);

		$this->log_registered();
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

			$event = new FilterAllowedBlockTypesEvent(
				$valid_allowed,
				$valid_context,
				$this->context_factory->create(),
			);

			$this->dispatcher->dispatch( $event );

		} catch ( InvalidBridgeArgumentException $e ) {

			$this->log_invalid_input( $e );

			return $allowed;

		} catch ( Throwable $e ) {

			$this->log_dispatch_failed( $e );

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
					'returned_type' => is_bool( $returned ) ? 'bool' : 'array',
					'returned_count' => is_array( $returned ) ? count( $returned ) : null,
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
