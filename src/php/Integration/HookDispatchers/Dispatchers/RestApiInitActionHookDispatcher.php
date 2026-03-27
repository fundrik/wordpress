<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers\Dispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\HookDispatchers\InvalidHookDispatcherArgumentException;
use Override;
use Throwable;
use WP_REST_Server;

/**
 * Dispatches the WordPress 'rest_api_init' action to attached listeners.
 *
 * Validates the action input before dispatching it to listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class RestApiInitActionHookDispatcher implements HookDispatcherInterface {

	private const string HOOK_NAME = 'rest_api_init';

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
	 * Registers the WordPress action callback.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_action( self::HOOK_NAME, $this->handle( ... ), 10, 1 );
	}

	/**
	 * Handles the WordPress action and dispatches it to listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $server The REST server instance.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function handle( mixed $server ): void {

		try {

			$valid_server = $this->validate_server( $server );
			$this->dispatch_to_listeners( $valid_server );

		} catch ( InvalidHookDispatcherArgumentException $e ) {

			$this->logger->log_invalid_input( $e );
			fundrik_set_failure_message( $e->getMessage() );
			return;

		} catch ( Throwable $e ) {

			// Listener exceptions must be logged in listener/BootUnit to avoid duplicate logs here.
			fundrik_set_failure_message( $e->getMessage() );
			return;
		}
	}

	/**
	 * Dispatches the validated hook argument to attached listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Server $server The validated REST server instance.
	 */
	private function dispatch_to_listeners( WP_REST_Server $server ): void {

		foreach ( $this->listeners as $listener ) {
			$listener( $server );
		}
	}

	/**
	 * Validates the 'server' argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $server The incoming REST server instance.
	 *
	 * @return WP_REST_Server The validated REST server instance.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function validate_server( mixed $server ): WP_REST_Server {

		if ( ! $server instanceof WP_REST_Server ) {
			throw InvalidHookDispatcherArgumentException::create( argument: 'server', hook: self::HOOK_NAME );
		}

		return $server;
	}
}
