<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\DeletePostActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\EnqueueBlockEditorAssetsActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestAfterInsertCampaignActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPreInsertCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPrepareCampaignFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistry;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( HookDispatcherRegistry::class )]
final class HookDispatcherRegistryTest extends FundrikTestCase {

	#[Test]
	public function it_returns_expected_dispatcher_class_names(): void {

		$registry = new HookDispatcherRegistry();

		self::assertSame(
			[
				AllowedBlockTypesAllFilterHookDispatcher::class,
				DeletePostActionHookDispatcher::class,
				EnqueueBlockEditorAssetsActionHookDispatcher::class,
				InitActionHookDispatcher::class,
				RestAfterInsertCampaignActionHookDispatcher::class,
				RestPreInsertCampaignFilterHookDispatcher::class,
				RestPrepareCampaignFilterHookDispatcher::class,
			],
			$registry->get_dispatcher_classes(),
		);
	}
}
