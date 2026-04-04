<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Helpers;

use Fundrik\Toolbox\TypeCaster;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Provides typed reading helpers for WordPress metadata.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class MetaReader {

	/**
	 * Finds the post meta value as a boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to read meta from.
	 * @param string $meta_key The meta key to read.
	 *
	 * @return bool|null The stored boolean value, or null when the meta key does not exist.
	 *
	 * @throws UnexpectedValueException When the stored value cannot be cast to a boolean.
	 */
	public static function find_post_meta_bool( int $post_id, string $meta_key ): ?bool {

		$value = self::find_post_meta( $post_id, $meta_key );

		if ( $value === null ) {
			return null;
		}

		if ( $value === '' ) {
			return false;
		}

		return self::cast_meta_value( $meta_key, 'bool', $value, TypeCaster::to_bool( ... ) );
	}

	/**
	 * Finds the post meta value as an integer.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to read meta from.
	 * @param string $meta_key The meta key to read.
	 *
	 * @return int|null The stored integer value, or null when the meta key does not exist.
	 *
	 * @throws UnexpectedValueException When the stored value cannot be cast to an integer.
	 */
	public static function find_post_meta_int( int $post_id, string $meta_key ): ?int {

		$value = self::find_post_meta( $post_id, $meta_key );

		if ( $value === null || $value === '' ) {
			return null;
		}

		return self::cast_meta_value( $meta_key, 'int', $value, TypeCaster::to_int( ... ) );
	}

	/**
	 * Finds the post meta value as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to read meta from.
	 * @param string $meta_key The meta key to read.
	 *
	 * @return string|null The stored string value, or null when the meta key does not exist.
	 *
	 * @throws UnexpectedValueException When the stored value cannot be cast to a string.
	 */
	public static function find_post_meta_string( int $post_id, string $meta_key ): ?string {

		$value = self::find_post_meta( $post_id, $meta_key );

		if ( $value === null || $value === '' ) {
			return null;
		}

		return self::cast_meta_value( $meta_key, 'string', $value, TypeCaster::to_string( ... ) );
	}

	/**
	 * Finds the post meta value by key.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to read meta from.
	 * @param string $meta_key The meta key to read.
	 *
	 * @return mixed|null The stored meta value, or null when the meta key does not exist.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private static function find_post_meta( int $post_id, string $meta_key ): mixed {

		if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
			return null;
		}

		return get_post_meta( $post_id, $meta_key, true );
	}

	/**
	 * Casts a stored post meta value and wraps cast failures with meta context.
	 *
	 * @since 1.0.0
	 *
	 * @template T of bool|int|string
	 *
	 * @param string $meta_key Meta key.
	 * @param string $expected_type Expected scalar type.
	 * @param mixed $value Stored meta value.
	 * @param callable $caster Value caster.
	 *
	 * @phpstan-param callable(mixed): T $caster
	 *
	 * @return T Cast value.
	 *
	 * @throws UnexpectedValueException When the stored value cannot be cast.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private static function cast_meta_value(
		string $meta_key,
		string $expected_type,
		mixed $value,
		callable $caster,
	): bool|int|string {

		try {
			return $caster( $value );
		} catch ( InvalidArgumentException $e ) {
			throw new UnexpectedValueException(
				sprintf(
					'Post meta "%s" must be %s. Given: %s.',
					$meta_key,
					$expected_type,
					get_debug_type( $value ),
				),
				previous: $e,
			);
		}
	}
}
