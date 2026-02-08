<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes\Attributes;

use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;

/**
 * Extracts the post type specific block list via repeatable #[PostTypeSpecificBlock] attributes.
 *
 * Ensures that a post type declares its specific blocks.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeSpecificBlockReader {

	/**
	 * Returns the list of specific block names.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the post type.
	 *
	 * @phpstan-param class-string $class_name
	 *
	 * @return array<string> The list of specific block names.
	 *
	 * @phpstan-return list<string>
	 *
	 * @throws RuntimeException When the post type does not declare any specific blocks.
	 */
	public function get_blocks( string $class_name ): array {

		$attributes = ( new ReflectionClass( $class_name ) )->getAttributes( PostTypeSpecificBlock::class );

		if ( $attributes === [] ) {

			throw new RuntimeException(
				sprintf(
					'Post type specific blocks must be declared via #[PostTypeSpecificBlock] attribute. Given: %s.',
					$class_name,
				),
			);
		}

		return array_map(
			static fn ( ReflectionAttribute $attribute ): string => $attribute->newInstance()->value,
			$attributes,
		);
	}
}
