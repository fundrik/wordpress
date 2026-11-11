<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress admin screen for tests.
 */
final class WpScreen {

	/**
	 * Current screen post type.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress WP_Screen API.
	public ?string $post_type;

	/**
	 * Creates the admin screen.
	 *
	 * @param string|null $post_type Current screen post type, if configured.
	 */
	public function __construct( ?string $post_type = null ) {

		$this->post_type = $post_type;
	}
}
