<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Logger;
use Override;
use Psr\Log\LoggerInterface;

/**
 * Provides a concrete structured logger for tests.
 */
final class TestLogger extends Logger {

	public function __construct( LoggerInterface $logger ) {

		parent::__construct( $logger, 'tests', 'infrastructure' );
	}

	#[Override]
	protected function base_context(): array {

		return parent::base_context() + [
			'scope' => 'fixture',
		];
	}
}
