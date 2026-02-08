<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookToEventBridges\Bridges;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\HookToEventBridges\BridgeLogger;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\EnqueueBlockEditorAssetsActionBridge;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( EnqueueBlockEditorAssetsActionBridge::class )]
#[UsesClass( BridgeLogger::class )]
final class EnqueueBlockEditorAssetsActionBridgeTest extends WordPressTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private BridgeLogger $logger;

	private EnqueueBlockEditorAssetsActionBridge $bridge;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class )->shouldIgnoreMissing();
		$this->logger = new BridgeLogger( $this->psr_logger );

		$this->bridge = new EnqueueBlockEditorAssetsActionBridge( $this->logger );
	}

	#[Test]
	public function register_registers_action(): void {

		$this->bridge->register();

		self::assertSame( 10, has_action( 'enqueue_block_editor_assets', $this->bridge->handle( ... ) ) );
	}

	#[Test]
	public function handle_enqueues_editor_script_with_expected_arguments(): void {

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with(
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

		$this->bridge->handle();
	}
}
