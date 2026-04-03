<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\WordPress\Integration\WpSchemaType;

/**
 * Represents a declared post meta field definition.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeMetaFieldDefinition {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_key Post meta key.
	 * @param WpSchemaType $type Value type.
	 * @param int|string|bool|null $default_value Default value, if configured.
	 */
	public function __construct(
		public string $meta_key,
		public WpSchemaType $type,
		public int|string|bool|null $default_value = null,
	) {}

	/**
	 * Returns registration arguments for WordPress post meta.
	 *
	 * @since 1.0.0
	 *
	 * @return array{type: string, default?: int|string|bool} Registration arguments.
	 */
	public function to_wp_args(): array {

		$result = [
			'type' => $this->type->value,
		];

		if ( $this->default_value !== null ) {
			$result['default'] = $this->default_value;
		}

		return $result;
	}
}
