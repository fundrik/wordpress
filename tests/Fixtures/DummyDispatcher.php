<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;

final class DummyDispatcher implements HookDispatcherInterface {

	// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	public function attach( callable $listener ): void {

		// Intentionally empty for fixture.
	}

	public function register(): void {

		// Intentionally empty for fixture.
	}
}
