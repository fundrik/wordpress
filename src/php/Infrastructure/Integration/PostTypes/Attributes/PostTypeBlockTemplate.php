<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes;

use Attribute;

/**
 * Declares the block-based editor template layout for the post type.
 *
 * It corresponds to the `template` argument in `register_post_type()`.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[Attribute( Attribute::TARGET_CLASS )]
final readonly class PostTypeBlockTemplate {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<array<string>> $value The nested layout array of block names.
	 *
	 * @phpstan-param list<list<string>>
	 */
	public function __construct(
		public array $value,
	) {}
}
