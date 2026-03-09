<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Throwable;

/**
 * Dispatches the WordPress 'enqueue_block_editor_assets' action to attached listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class EnqueueBlockEditorAssetsActionHookDispatcher implements HookDispatcherInterface {

	private const string HOOK_NAME = 'enqueue_block_editor_assets';

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
	public function attach( callable $listener ): void {

		$this->listeners[] = $listener;
	}

	/**
	 * Registers the WordPress action callback.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_action( self::HOOK_NAME, $this->handle( ... ) );
	}

	/**
	 * Handles the WordPress action and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 */
	private function handle(): void {

		try {

			foreach ( $this->listeners as $listener ) {
				$listener();
			}
		} catch ( Throwable $e ) {

			// Listener exceptions must be logged in listener/BootUnit to avoid duplicate logs here.
			fundrik_set_failure_message( $e->getMessage() );
			return;
		}
	}
}
