<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookToEventBridges\Bridges;

use Fundrik\WordPress\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Integration\HookToEventBridges\HookToEventBridgeInterface;

/**
 * Bridges the WordPress 'enqueue_block_editor_assets' action to editor-specific asset registration.
 *
 * This bridge performs direct hook handling without dispatching infrastructure events,
 * as the operation is limited to UI asset wiring and does not represent an extension contract.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class EnqueueBlockEditorAssetsActionBridge implements HookToEventBridgeInterface {

	/**
	 * The WordPress hook name handled by this bridge.
	 *
	 * @since 1.0.0
	 */
	private const string HOOK_NAME = 'enqueue_block_editor_assets';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param BridgeLogger $logger Writes structured log entries for this hook bridge.
	 */
	public function __construct(
		private BridgeLogger $logger,
	) {

		$this->logger->set_hook_name( self::HOOK_NAME );
		$this->logger->set_bridge_class( self::class );
	}

	/**
	 * Registers the WordPress hook handled by this bridge.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {

		add_action( self::HOOK_NAME, $this->handle( ... ) );

		$this->logger->log_registered();
	}

	/**
	 * Enqueues the block editor scripts required by the plugin.
	 *
	 * @since 1.0.0
	 */
	public function handle(): void {

		wp_enqueue_script(
			'fundrik-editor-save-sync',
			FUNDRIK_URL . '/assets/js/fundrik-editor-save-sync.js',
			[
				'wp-data',
				'wp-core-data',
				'wp-editor',
				'wp-api-fetch',
			],
			FUNDRIK_VERSION,
			[ 'in_footer' => true ],
		);

		$this->logger->log_handled(
			'enqueued',
			[
				'script_handle' => 'fundrik-editor-save-sync',
			],
		);
	}
}
