<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\Toolbox\TypeCaster;
use InvalidArgumentException;
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
	 * Returns meta field definitions from a post type config class.
	 *
	 * @since 1.0.0
	 *
	 * @param PostTypeConfigInterface $post_type_config The post type config.
	 *
	 * @return array<string, array<string, int|string|bool>> The declared meta fields.
	 *
	 * @phpstan-return array<string, array{
	 *   type: string,
	 *   default?: int|string|bool,
	 * }>
	 *
	 * @throws InvalidArgumentException When a post meta key constant value is not a string.
	 */
	public function get_meta_fields( PostTypeConfigInterface $post_type_config ): array {

		$reflection = new ReflectionClass( $post_type_config::class );

		$fields = [];

		foreach ( $reflection->getReflectionConstants() as $constant ) {

			foreach ( $constant->getAttributes( PostTypeMetaField::class ) as $attribute ) {

				$fields[ TypeCaster::to_string( $constant->getValue() ) ] = $attribute->newInstance()->to_array();
			}
		}

		return $fields;
	}
}
