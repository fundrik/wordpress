<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Clock;

use Fundrik\Core\Components\Shared\Domain\UtcDateTime;

/**
 * Provides access to current UTC time.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface ClockInterface {

	/**
	 * Returns current UTC time.
	 *
	 * @since 1.0.0
	 *
	 * @return UtcDateTime Current UTC time.
	 */
	public function now(): UtcDateTime;
}
