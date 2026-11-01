<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Infrastructure\Clock\ClockInterface;
use Override;

/**
 * Returns a fixed UTC time for tests.
 */
final readonly class FrozenClock implements ClockInterface {

	public function __construct(
		private UtcDateTime $time,
	) {}

	#[Override]
	public function now(): UtcDateTime {

		return $this->time;
	}
}
