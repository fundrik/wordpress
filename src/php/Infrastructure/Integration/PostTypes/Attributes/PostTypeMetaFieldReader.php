<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes;

use Fundrik\Toolbox\TypeCaster;
use ReflectionClass;

/**
 * Extracts post type meta fields via the #[PostTypeMetaField] attribute from class constants.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeMetaFieldReader {

	/**
	 * Returns meta field definitions from a post type class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name The fully qualified class name of the post type.
	 *
	 * @phpstan-param class-string $class_name
	 *
	 * @return array<string, array<string, int|string|bool>> The declared meta fields.
	 *
	 * @phpstan-return array<string, array{
	 *   type: string,
	 *   default?: int|string|bool,
	 * }>
	 */
	public function get_meta_fields( string $class_name ): array {

		$reflection = new ReflectionClass( $class_name );

		$fields = [];

		foreach ( $reflection->getReflectionConstants() as $constant ) {

			foreach ( $constant->getAttributes( PostTypeMetaField::class ) as $attribute ) {

				$fields[ TypeCaster::to_string( $constant->getValue() ) ] = $attribute->newInstance()->to_array();
			}
		}

		return $fields;
	}
}
