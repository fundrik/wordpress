<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookToEventBridges;

use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\AllowedBlockTypesAllFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\DeletePostActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\EnqueueBlockEditorAssetsActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\InitActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\RestAfterInsertCampaignActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\RestPreInsertCampaignFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\RestPrepareCampaignFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\WpAfterInsertPostActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\HookBridgeRegistry;
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
				RestAfterInsertCampaignActionBridge::class,
				RestPreInsertCampaignFilterBridge::class,
				RestPrepareCampaignFilterBridge::class,
				WpAfterInsertPostActionBridge::class,
			],
			$registry->get_bridge_classes(),
		);
	}
}
