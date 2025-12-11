<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes;

use Attribute;

/**
 * Declares a block that is specifically allowed for this post type only.
 *
 * If a block is declared in one or more post types, it will be restricted
 * to those post types and disallowed elsewhere.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE )]
final readonly class PostTypeSpecificBlock {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The block name.
	 */
	public function __construct(
		public string $value,
	) {}
}
