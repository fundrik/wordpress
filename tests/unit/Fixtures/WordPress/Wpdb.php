<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress database connection for tests.
 */
// phpcs:ignore FundrikStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal
class Wpdb {

	/**
	 * Last database error message.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress wpdb API.
	public string $last_error = '';

	/**
	 * WordPress table prefix.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress wpdb API.
	public string $prefix = 'wp_';
}
