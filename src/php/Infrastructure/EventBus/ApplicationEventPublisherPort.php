<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventBus;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;

/**
 * Provides the outbound port for publishing application events.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface ApplicationEventPublisherPort {

	/**
	 * Publishes the given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event The application event.
	 */
	public function publish( ApplicationEventInterface $event ): void;
}
