<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Logger;
use Psr\Log\LoggerInterface;

final class DummyLogger extends Logger {

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger backend.
	 */
	public function __construct( LoggerInterface $logger ) {

		parent::__construct(
			$logger,
			'tests',
			'tests',
		);
	}
}
