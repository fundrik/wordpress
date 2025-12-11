<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes;

use ReflectionClass;
use RuntimeException;

/**
 * Extracts the post type block editor template via the #[PostTypeBlockTemplate] attribute.
 *
 * Ensures that a post type declares its block editor template.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeBlockTemplateReader {

	/**
	 * Returns the block editor template from a post type class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the post type.
	 *
	 * @phpstan-param class-string $class_name
	 *
	 * @return array<int, array<int, string>> The declared block editor template.
	 *
	 * @phpstan-return list<list<string>>
	 *
	 * @throws RuntimeException When the post type does not declare a block template attribute.
	 */
	public function get_template( string $class_name ): array {

		$attributes = ( new ReflectionClass( $class_name ) )->getAttributes( PostTypeBlockTemplate::class );

		if ( $attributes === [] ) {

			throw new RuntimeException(
				sprintf(
					'Post type block template must be declared via #[PostTypeBlockTemplate] attribute. Given: %s.',
					$class_name,
				),
			);
		}

		return $attributes[0]->newInstance()->value;
	}
}
