<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress REST server for tests.
 */
final class WpRestServer {

	/**
	 * HTTP method used for creatable REST routes.
	 */
	public const string CREATABLE = 'POST';
}
