<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages;

/**
 * Represents a WordPress admin page registration.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface AdminPageInterface {

	/**
	 * Registers the admin page in WordPress.
	 *
	 * @since 1.0.0
	 */
	public function register(): void;
}
