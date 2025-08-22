<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges;

use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\AllowedBlockTypesAllFilterBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\DeletePostActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\InitActionBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\RestPreInsertCampaignFilterBridge;
use Fundrik\WordPress\Infrastructure\Integration\HookToEventBridges\Bridges\WpAfterInsertPostActionBridge;

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
			InitActionBridge::class,
			RestPreInsertCampaignFilterBridge::class,
			WpAfterInsertPostActionBridge::class,
		];
	}
}
