<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\WpSchemaType;
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
	 * @return array<string, PostTypeMetaFieldDefinition> Declared meta fields keyed by meta key.
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

		if ( $field?->default_value === null ) {
			return null;
		}

		$this->validate_meta_field_default_or_fail( $post_type_config_class, $field );

		return $field->default_value;
	}

	/**
	 * Returns cached meta field definitions by post type config class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 *
	 * @return array<string, PostTypeMetaFieldDefinition> Declared meta fields keyed by meta key.
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
				$meta_key = TypeCaster::to_string( $constant->getValue() );

				$fields[ $meta_key ] = $attribute->newInstance()->to_definition( $meta_key );
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
	 * @return PostTypeMetaFieldDefinition|null Meta field definition, or null when missing.
	 */
	private function get_meta_field_by_config_class(
		string $post_type_config_class,
		string $meta_key,
	): ?PostTypeMetaFieldDefinition {

		$fields = $this->get_meta_fields_by_config_class( $post_type_config_class );

		return $fields[ $meta_key ] ?? null;
	}

	/**
	 * Validates default values against their declared meta field types.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 * @param array<string, PostTypeMetaFieldDefinition> $fields Declared meta fields keyed by meta key.
	 *
	 * @throws InvalidArgumentException When a default value does not match the declared type.
	 */
	private function validate_meta_field_defaults_or_fail( string $post_type_config_class, array $fields ): void {

		foreach ( $fields as $field ) {

			if ( $field->default_value === null ) {
				continue;
			}

			$this->validate_meta_field_default_or_fail( $post_type_config_class, $field );
		}
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Validates a meta field default against its declared type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_config_class The post type config class name.
	 * @param PostTypeMetaFieldDefinition $field Meta field definition.
	 *
	 * @throws InvalidArgumentException When the default value does not match the declared type.
	 */
	private function validate_meta_field_default_or_fail(
		string $post_type_config_class,
		PostTypeMetaFieldDefinition $field,
	): void {

		$default = $field->default_value;

		if ( $default === null ) {
			return;
		}

		$is_valid = match ( $field->type ) {
			WpSchemaType::Boolean => is_bool( $default ),
			WpSchemaType::Integer => is_int( $default ),
			WpSchemaType::String => is_string( $default ),
			default => false,
		};

		if ( $is_valid ) {
			return;
		}

		throw new InvalidArgumentException(
			sprintf(
				'Post meta default for "%s" in "%s" must match "%s". Given: %s.',
				$field->meta_key,
				$post_type_config_class,
				$field->type->value,
				get_debug_type( $default ),
			),
		);
	}
	// phpcs:enable
}
