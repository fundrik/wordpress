<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;

final class DummyBootUnit implements BootUnitInterface {

	public function boot(): void {

		// Intentionally empty for fixture.
	}
}
