<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

// phpcs:ignore FundrikStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class InvokableListenerSpy {

	public function __invoke(): void {
		// Intentionally empty for spy.
	}
}
