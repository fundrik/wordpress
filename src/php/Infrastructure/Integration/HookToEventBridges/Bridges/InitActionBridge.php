<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Throwable;

/**
 * Bridges the WordPress 'init' action to internal integration events.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class InitActionBridge implements HookToEventBridgeInterface {

	private const HOOK_NAME = 'init';

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
	 * Registers the 'init' WordPress action and bridge it to the internal events.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_action( self::HOOK_NAME, $this->handle( ... ) );

		$this->logger->log_registered();
	}

	/**
	 * Handles the 'init' action logic.
	 *
	 * Dispatch the registration events and log the outcome.
	 *
	 * @since 1.0.0
	 */
	public function handle(): void {

		try {
			$this->dispatcher->dispatch( new RegisterPostTypesEvent( $this->context ) );
		} catch ( Throwable $e ) {
			$this->logger->log_dispatch_failed( $e );
			throw $e;
		}

		try {
			$this->dispatcher->dispatch( new RegisterBlocksEvent( $this->context ) );
		} catch ( Throwable $e ) {
			$this->logger->log_dispatch_failed( $e );
			throw $e;
		}

		$this->log_handled(
			outcome: 'dispatched',
			events: [ 'RegisterPostTypesEvent', 'RegisterBlocksEvent' ],
		);
	}

	/**
	 * Builds and delegates the final handle log entry to the logger (debug).
	 *
	 * @since 1.0.0
	 *
	 * @param string $outcome The action bridge outcome.
	 * @param array<string> $events The list of dispatched events.
	 */
	private function log_handled( string $outcome, array $events ): void {

		$this->logger->log_handled(
			$outcome,
			[
				'event_count' => count( $events ),
				'events' => $events,
			],
		);
	}
}
