<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerRegistry;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\ActionCampaignSavedViaRestEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterCampaignBeforeSavedViaRestEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterCampaignRestResponseEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\ActionRegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\ActionRegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\EnsureCampaignPostCanBeSyncedListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\FilterAllowedBlocksByPostTypeListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\ProvideCampaignVersionForSyncListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterBlocksListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterPostTypesListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\SyncCampaignAfterRestSaveListener;
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
