<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventBus;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;

/**
 * Provides methods for consuming application events.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface ApplicationEventConsumerInterface {

	/**
	 * Consumes the given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 */
	public function consume( ApplicationEventInterface $event ): void;
}
