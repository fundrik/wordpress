<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes\Attributes;

use Attribute;

/**
 * Declares the identifier used to register the post type in WordPress.
 *
 * It corresponds to the `post_type` argument in `register_post_type()`.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[Attribute( Attribute::TARGET_CLASS )]
final readonly class PostTypeId {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The post type id.
	 */
	public function __construct(
		public string $value,
	) {}
}
