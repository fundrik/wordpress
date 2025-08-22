<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventDispatcherInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextFactory;
use Psr\Log\LoggerInterface;
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
	 * @param WordPressContextFactory $context_factory Creates WordPressContext instances on demand.
	 * @param EventDispatcherInterface $dispatcher Dispatches the bridged events.
	 * @param LoggerInterface $logger Logs registration and dispatch outcomes.
	 */
	public function __construct(
		private WordPressContextFactory $context_factory,
		private EventDispatcherInterface $dispatcher,
		private LoggerInterface $logger,
	) {}

	/**
	 * Registers the 'init' WordPress action and bridge it to the internal events.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_action(
			self::HOOK_NAME,
			$this->handle( ... ),
		);

		$this->log_registered();
	}

	/**
	 * Handles the 'init' action logic.
	 *
	 * Dispatch the registration events and log the outcome.
	 *
	 * @since 1.0.0
	 */
	public function handle(): void {

		$context = $this->context_factory->create();

		try {
			$this->dispatcher->dispatch( new RegisterPostTypesEvent( $context ) );
		} catch ( Throwable $e ) {
			$this->log_dispatch_failed( $e, 'RegisterPostTypesEvent' );
			throw $e;
		}

		try {
			$this->dispatcher->dispatch( new RegisterBlocksEvent( $context ) );
		} catch ( Throwable $e ) {
			$this->log_dispatch_failed( $e, 'RegisterBlocksEvent' );
			throw $e;
		}

		$this->log_handled(
			outcome: 'dispatched',
			events: [ 'RegisterPostTypesEvent', 'RegisterBlocksEvent' ],
		);
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
	 * Logs that the dispatch stage failed due to an exception in listeners.
	 *
	 * @since 1.0.0
	 *
	 * @param Throwable $e The thrown exception from the dispatch stage.
	 * @param string $event The event being dispatched when the error occurred.
	 */
	private function log_dispatch_failed( Throwable $e, string $event ): void {

		$this->logger->error(
			sprintf( "Bridge dispatch failed for hook '%s'.", self::HOOK_NAME ),
			$this->logger_context(
				[
					'stage' => 'dispatch',
					'outcome' => 'error',
					'invoked' => true,
					'event' => $event,
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
	 * @param string $outcome The action bridge outcome.
	 * @param array<int, string> $events The list of dispatched events.
	 */
	private function log_handled( string $outcome, array $events ): void {

		$this->logger->debug(
			'Hook bridge handled.',
			$this->logger_context(
				[
					'outcome' => $outcome,
					'invoked' => true,
					'event_count' => count( $events ),
					'events' => $events,
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
