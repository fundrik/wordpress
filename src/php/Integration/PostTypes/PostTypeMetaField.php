<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Attribute;

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
	 * @param PostTypeMetaFieldType $type The value type of the meta field.
	 * @param int|string|bool|null $default Optional default value for the meta field.
	 */
	public function __construct(
		public PostTypeMetaFieldType $type,
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
	 * } The key-value representation of the meta field configuration.
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
