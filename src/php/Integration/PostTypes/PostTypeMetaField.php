<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Attribute;
use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Declares a post meta field associated with a post type config constant.
 *
 * @since 1.0.0
 *
 * @internal
 */
#[Attribute( Attribute::TARGET_CLASS_CONSTANT )]
final readonly class PostTypeMetaField {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WpSchemaType $type Value type.
	 * @param int|string|bool|null $default Optional default value.
	 */
	public function __construct(
		public WpSchemaType $type,
		// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
		public int|string|bool|null $default = null,
	) {}

	/**
	 * Creates a declared post meta field definition.
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_key Post meta key.
	 *
	 * @return PostTypeMetaFieldDefinition Declared meta field definition.
	 */
	public function to_definition( string $meta_key ): PostTypeMetaFieldDefinition {

		return new PostTypeMetaFieldDefinition( $meta_key, $this->type, $this->default );
	}
}
