<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookToEventBridges;

use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\AllowedBlockTypesAllFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\DeletePostActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\EnqueueBlockEditorAssetsActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\InitActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\RestAfterInsertCampaignActionBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\RestPreInsertCampaignFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\RestPrepareCampaignFilterBridge;
use Fundrik\WordPress\Integration\HookToEventBridges\Bridges\WpAfterInsertPostActionBridge;

/**
 * Provides the list of bridge classes.
 *
 * @since 1.0.0
 *
 * @internal
 */
class HookBridgeRegistry {

	/**
	 * Returns the list of bridge class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> The list of bridge classes.
	 *
	 * @phpstan-return list<class-string<HookToEventBridgeInterface>>
	 */
	public function get_bridge_classes(): array {

		return [
			AllowedBlockTypesAllFilterBridge::class,
			DeletePostActionBridge::class,
			EnqueueBlockEditorAssetsActionBridge::class,
			InitActionBridge::class,
			RestAfterInsertCampaignActionBridge::class,
			RestPreInsertCampaignFilterBridge::class,
			RestPrepareCampaignFilterBridge::class,
			WpAfterInsertPostActionBridge::class,
		];
	}
}
