<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\HookToEventBridges;

use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\AllowedBlockTypesAllFilterBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\DeletePostActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\EnqueueBlockEditorAssetsActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\InitActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\RestPreInsertCampaignFilterBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\RestPrepareCampaignFilterBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\WpAfterInsertPostActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\HookBridgeRegistry;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( HookBridgeRegistry::class )]
final class HookBridgeRegistryTest extends FundrikTestCase {

	#[Test]
	public function it_returns_expected_bridge_class_names(): void {

		$registry = new HookBridgeRegistry();

		$this->assertSame(
			[
				AllowedBlockTypesAllFilterBridge::class,
				DeletePostActionBridge::class,
				EnqueueBlockEditorAssetsActionBridge::class,
				InitActionBridge::class,
				RestPreInsertCampaignFilterBridge::class,
				RestPrepareCampaignFilterBridge::class,
				WpAfterInsertPostActionBridge::class,
			],
			$registry->get_bridge_classes(),
		);
	}
}
