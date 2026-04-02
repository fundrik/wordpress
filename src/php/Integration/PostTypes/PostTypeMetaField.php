<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Attribute;
use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Declares a post meta field associated with a post type config constant.
 *
 * This attribute is applied to class constants that represent post meta keys.
 * It provides type information and an optional default value, used during
 * post type registration.
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
	 * Converts the attribute into an associative array.
	 *
	 * @since 1.0.0
	 *
	 * @return array{
	 *   type: string,
	 *   default?: int|string|bool
	 * } Key-value representation.
	 */
	public function to_array(): array {

		$result = [
			'type' => $this->type->value,
		];

		if ( $this->default !== null ) {
			$result['default'] = $this->default;
		}

		return $result;
	}
}
