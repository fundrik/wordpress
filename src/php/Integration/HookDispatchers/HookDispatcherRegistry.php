<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;

/**
 * Provides the list of hook dispatcher classes.
 *
 * @since 1.0.0
 *
 * @internal
 */
class HookDispatcherRegistry {

	/**
	 * Returns the list of hook dispatcher class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> The list of hook dispatcher classes.
	 *
	 * @phpstan-return list<class-string<HookDispatcherInterface>>
	 */
	public function get_dispatcher_classes(): array {

		return [
			AllowedBlockTypesAllFilterHookDispatcher::class,
			DeletePostActionHookDispatcher::class,
			EnqueueBlockEditorAssetsActionHookDispatcher::class,
			InitActionHookDispatcher::class,
			RestApiInitActionHookDispatcher::class,
			RestAfterInsertCampaignActionHookDispatcher::class,
			RestPreInsertCampaignFilterHookDispatcher::class,
			RestPrepareCampaignFilterHookDispatcher::class,
		];
	}
}
