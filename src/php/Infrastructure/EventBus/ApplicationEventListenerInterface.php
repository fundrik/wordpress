<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventBus;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;

/**
 * Provides methods for handling application events.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface ApplicationEventListenerInterface {

	/**
	 * Handles the given event.
	 *
	 * @since 1.0.0
	 *
	 * @param ApplicationEventInterface $event Application event.
	 */
	public function handle( ApplicationEventInterface $event ): void;
}
