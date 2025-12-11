<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes;

use ReflectionClass;
use RuntimeException;

/**
 * Extracts the post type ID via the #[PostTypeId] attribute.
 *
 * Ensures that a post type declares its ID.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeIdReader {

	/**
	 * Returns the ID from a post type class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the post type.
	 *
	 * @phpstan-param class-string $class_name
	 *
	 * @return string The declared post type ID.
	 *
	 * @throws RuntimeException When the post type does not declare an ID attribute.
	 */
	public function get_id( string $class_name ): string {

		$attributes = ( new ReflectionClass( $class_name ) )->getAttributes( PostTypeId::class );

		if ( $attributes === [] ) {

			throw new RuntimeException(
				sprintf(
					'Post type ID must be declared via #[PostTypeId] attribute. Given: %s.',
					$class_name,
				),
			);
		}

		return $attributes[0]->newInstance()->value;
	}
}
