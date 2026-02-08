<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes\Attributes;

use Attribute;

/**
 * Declares the slug used in rewrite rules for the post type.
 *
 * It corresponds to the `rewrite['slug']` argument in `register_post_type()`.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[Attribute( Attribute::TARGET_CLASS )]
final readonly class PostTypeSlug {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The slug for the post type URLs.
	 */
	public function __construct(
		public string $value,
	) {}
}
