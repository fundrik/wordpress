<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures\WordPress;

/**
 * Provides a minimal WordPress block editor context for tests.
 */
final class WpBlockEditorContext {

	/**
	 * Post being edited, if configured.
	 */
	// phpcs:ignore SlevomatCodingStandard.Classes.ForbiddenPublicProperty.ForbiddenPublicProperty -- Matches the WordPress WP_Block_Editor_Context API.
	public ?WpPost $post;

	/**
	 * Creates the block editor context.
	 *
	 * @param WpPost|null $post Post being edited, if configured.
	 */
	public function __construct( ?WpPost $post = null ) {

		$this->post = $post;
	}
}
