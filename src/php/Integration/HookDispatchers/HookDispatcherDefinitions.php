<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminMenuActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\BlockCategoriesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPostDispatchFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;

/**
 * Provides hook dispatcher declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class HookDispatcherDefinitions {

	/**
	 * Returns the configured hook dispatcher classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<HookDispatcherInterface>>
	 */
	public static function classes(): array {

		return [
			AdminInitActionHookDispatcher::class,
			AdminMenuActionHookDispatcher::class,
			BlockCategoriesAllFilterHookDispatcher::class,
			AllowedBlockTypesAllFilterHookDispatcher::class,
			DeletePostActionHookDispatcher::class,
			EnqueueBlockEditorAssetsActionHookDispatcher::class,
			InitActionHookDispatcher::class,
			RestApiInitActionHookDispatcher::class,
			RestAfterInsertCampaignActionHookDispatcher::class,
			RestPostDispatchFilterHookDispatcher::class,
			RestPreInsertCampaignFilterHookDispatcher::class,
			RestPrepareCampaignFilterHookDispatcher::class,
		];
	}
}
