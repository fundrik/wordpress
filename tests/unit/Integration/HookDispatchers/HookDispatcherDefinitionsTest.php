<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminMenuActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\BlockCategoriesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPostDispatchFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherDefinitions;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( HookDispatcherDefinitions::class )]
final class HookDispatcherDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function it_exposes_expected_hook_dispatcher_classes(): void {

		$this->assertSame(
			[
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
			],
			HookDispatcherDefinitions::classes(),
		);
	}
}
