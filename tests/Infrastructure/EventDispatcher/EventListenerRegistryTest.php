<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerRegistry;
use Fundrik\WordPress\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Integration\Events\ActionCampaignSavedViaRestEvent;
use Fundrik\WordPress\Integration\Events\FilterCampaignBeforeSavedViaRestEvent;
use Fundrik\WordPress\Integration\Events\FilterCampaignRestResponseEvent;
use Fundrik\WordPress\Integration\Events\ActionRegisterBlocksEvent;
use Fundrik\WordPress\Integration\Events\ActionRegisterPostTypesEvent;
use Fundrik\WordPress\Integration\Listeners\EnsureCampaignPostCanBeSyncedListener;
use Fundrik\WordPress\Integration\Listeners\FilterAllowedBlocksByPostTypeListener;
use Fundrik\WordPress\Integration\Listeners\ProvideCampaignVersionForSyncListener;
use Fundrik\WordPress\Integration\Listeners\RegisterBlocksListener;
use Fundrik\WordPress\Integration\Listeners\RegisterPostTypesListener;
use Fundrik\WordPress\Integration\Listeners\SyncCampaignAfterRestSaveListener;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( EventListenerRegistry::class )]
final class EventListenerRegistryTest extends FundrikTestCase {

	#[Test]
	public function it_returns_event_listener_map(): void {

		$registry = new EventListenerRegistry();

		$this->assertSame(
			[
				ActionRegisterPostTypesEvent::class => RegisterPostTypesListener::class,
				ActionRegisterBlocksEvent::class => RegisterBlocksListener::class,
				FilterAllowedBlockTypesEvent::class => FilterAllowedBlocksByPostTypeListener::class,
				FilterCampaignRestResponseEvent::class => ProvideCampaignVersionForSyncListener::class,
				FilterCampaignBeforeSavedViaRestEvent::class => EnsureCampaignPostCanBeSyncedListener::class,
				ActionCampaignSavedViaRestEvent::class => SyncCampaignAfterRestSaveListener::class,
			],
			$registry->get_event_listener_map(),
		);
	}
}
