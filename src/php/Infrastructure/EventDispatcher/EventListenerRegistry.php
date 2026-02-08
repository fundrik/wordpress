<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Integration\Events\ActionCampaignSavedViaRestEvent;
use Fundrik\WordPress\Integration\Events\ActionRegisterBlocksEvent;
use Fundrik\WordPress\Integration\Events\ActionRegisterPostTypesEvent;
use Fundrik\WordPress\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Integration\Events\FilterCampaignBeforeSavedViaRestEvent;
use Fundrik\WordPress\Integration\Events\FilterCampaignRestResponseEvent;
use Fundrik\WordPress\Integration\Listeners\EnsureCampaignPostCanBeSyncedListener;
use Fundrik\WordPress\Integration\Listeners\FilterAllowedBlocksByPostTypeListener;
use Fundrik\WordPress\Integration\Listeners\ProvideCampaignVersionForSyncListener;
use Fundrik\WordPress\Integration\Listeners\RegisterBlocksListener;
use Fundrik\WordPress\Integration\Listeners\RegisterPostTypesListener;
use Fundrik\WordPress\Integration\Listeners\SyncCampaignAfterRestSaveListener;

/**
 * Provides the map of infrastructure events to their listeners.
 *
 * @since 1.0.0
 *
 * @internal
 */
class EventListenerRegistry {

	/**
	 * Returns the map of infrastructure event class names to listener class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> The event-to-listener map.
	 *
	 * @phpstan-return array<
	 *     class-string<InfrastructureEventInterface>,
	 *     class-string<InfrastructureEventListenerInterface>
	 * >
	 */
	public function get_event_listener_map(): array {

		return [
			ActionRegisterPostTypesEvent::class => RegisterPostTypesListener::class,
			ActionRegisterBlocksEvent::class => RegisterBlocksListener::class,
			FilterAllowedBlockTypesEvent::class => FilterAllowedBlocksByPostTypeListener::class,
			FilterCampaignRestResponseEvent::class => ProvideCampaignVersionForSyncListener::class,
			FilterCampaignBeforeSavedViaRestEvent::class => EnsureCampaignPostCanBeSyncedListener::class,
			ActionCampaignSavedViaRestEvent::class => SyncCampaignAfterRestSaveListener::class,
		];
	}
}
