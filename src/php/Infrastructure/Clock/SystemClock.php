<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Clock;

use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Override;

/**
 * Provides current UTC time from the system clock.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class SystemClock implements ClockInterface {

	/**
	 * Returns current UTC time.
	 *
	 * @since 1.0.0
	 *
	 * @return UtcDateTime Current UTC time.
	 */
	#[Override]
	public function now(): UtcDateTime {

		return UtcDateTime::now();
	}
}
