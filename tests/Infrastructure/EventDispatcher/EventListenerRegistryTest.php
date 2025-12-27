<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventDispatcher;

use Fundrik\WordPress\Infrastructure\EventDispatcher\EventListenerRegistry;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\Events\RegisterPostTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\FilterAllowedBlocksByPostTypeListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterBlocksListener;
use Fundrik\WordPress\Infrastructure\Integration\Listeners\RegisterPostTypesListener;
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
				RegisterPostTypesEvent::class => RegisterPostTypesListener::class,
				RegisterBlocksEvent::class => RegisterBlocksListener::class,
				FilterAllowedBlockTypesEvent::class => FilterAllowedBlocksByPostTypeListener::class,
			],
			$registry->get_event_listener_map(),
		);
	}
}
