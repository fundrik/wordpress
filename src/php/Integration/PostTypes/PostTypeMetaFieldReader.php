<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\WordPress\Integration\WpSchemaType;
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
	 * @return array<string, array{type: string, default?: int|string|bool}> The declared meta fields.
	 *
	 * @throws InvalidArgumentException When a post meta key constant value is not a string.
	 */
	public function get_meta_fields( PostTypeConfigInterface $post_type_config ): array {

		return $this->get_meta_fields_by_config_class( $post_type_config::class );
	}

	/**
	 * Returns the declared default value for a post meta field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 * @param string $meta_key The post meta key.
	 *
	 * @return int|string|bool|null The declared default value, or null when missing.
	 */
	public function get_meta_default_by_config_class(
		string $post_type_config_class,
		string $meta_key,
	): int|string|bool|null {

		$field = $this->get_meta_field_by_config_class( $post_type_config_class, $meta_key );

		if ( $field === null || ! array_key_exists( 'default', $field ) ) {
			return null;
		}

		$this->validate_meta_field_default_or_fail( $post_type_config_class, $meta_key, $field );

		return $field['default'];
	}

	/**
	 * Returns cached meta field definitions by post type config class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 *
	 * @return array<string, array{type: string, default?: int|string|bool}> The declared meta fields.
	 *
	 * @throws InvalidArgumentException When a post meta key constant value is not a string.
	 */
	private function get_meta_fields_by_config_class( string $post_type_config_class ): array {

		static $cache = [];

		if ( isset( $cache[ $post_type_config_class ] ) ) {
			return $cache[ $post_type_config_class ];
		}

		$reflection = new ReflectionClass( $post_type_config_class );

		$fields = [];

		foreach ( $reflection->getReflectionConstants() as $constant ) {

			foreach ( $constant->getAttributes( PostTypeMetaField::class ) as $attribute ) {

				$fields[ TypeCaster::to_string( $constant->getValue() ) ] = $attribute->newInstance()->to_array();
			}
		}

		$this->validate_meta_field_defaults_or_fail( $post_type_config_class, $fields );

		$cache[ $post_type_config_class ] = $fields;

		return $cache[ $post_type_config_class ];
	}

	/**
	 * Returns a single meta field definition by post type config class and key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 * @param string $meta_key The post meta key.
	 *
	 * @return array{type: string, default?: int|string|bool}|null The meta field definition, or null when missing.
	 */
	private function get_meta_field_by_config_class( string $post_type_config_class, string $meta_key ): ?array {

		$fields = $this->get_meta_fields_by_config_class( $post_type_config_class );

		return $fields[ $meta_key ] ?? null;
	}

	/**
	 * Validates default values against their declared meta field types.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 * @param array<string, array{type: string, default?: int|string|bool}> $fields The declared meta fields.
	 *
	 * @throws InvalidArgumentException When a default value does not match the declared type.
	 */
	private function validate_meta_field_defaults_or_fail( string $post_type_config_class, array $fields ): void {

		foreach ( $fields as $meta_key => $field ) {

			if ( ! array_key_exists( 'default', $field ) ) {
				continue;
			}

			$this->validate_meta_field_default_or_fail( $post_type_config_class, $meta_key, $field );
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Validates a meta field default against its declared type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 * @param string $meta_key The post meta key.
	 * @param array{type: string, default?: int|string|bool} $field The meta field definition.
	 *
	 * @throws InvalidArgumentException When the default value does not match the declared type.
	 */
	private function validate_meta_field_default_or_fail(
		string $post_type_config_class,
		string $meta_key,
		array $field,
	): void {

		$type = TypeCaster::to_string( $field['type'] ?? '' );
		$default = $field['default'] ?? null;

		if ( $default === null ) {
			return;
		}

		$is_valid = match ( $type ) {
			WpSchemaType::Boolean->value => is_bool( $default ),
			WpSchemaType::Integer->value => is_int( $default ),
			WpSchemaType::String->value => is_string( $default ),
			default => false,
		};

		if ( $is_valid ) {
			return;
		}

		throw new InvalidArgumentException(
			sprintf(
				'Post meta default for "%s" in "%s" must match "%s". Given: %s.',
				$meta_key,
				$post_type_config_class,
				$type,
				get_debug_type( $default ),
			),
		);
	}
	// phpcs:enable
}
