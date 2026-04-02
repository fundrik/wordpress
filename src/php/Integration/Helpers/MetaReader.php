<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Helpers;

use Fundrik\Toolbox\TypeCaster;

/**
 * Provides typed reading helpers for WordPress metadata.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class MetaReader {

	/**
	 * Returns the post meta value or null when the meta key does not exist.
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
	public static function get_post_meta_or_null( int $post_id, string $meta_key ): mixed {

		if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
			return null;
		}

		return get_post_meta( $post_id, $meta_key, true );
	}

	/**
	 * Returns the post meta value as a boolean or null when the meta key does not exist.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to read meta from.
	 * @param string $meta_key The meta key to read.
	 *
	 * @return bool|null The stored boolean value, or null when the meta key does not exist.
	 */
	public static function get_post_meta_bool_or_null( int $post_id, string $meta_key ): ?bool {

		$value = self::get_post_meta_or_null( $post_id, $meta_key );

		if ( $value === null ) {
			return null;
		}

		if ( $value === '' ) {
			return false;
		}

		return TypeCaster::to_bool( $value );
	}

	/**
	 * Returns the post meta value as an integer or null when the meta key does not exist.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to read meta from.
	 * @param string $meta_key The meta key to read.
	 *
	 * @return int|null The stored integer value, or null when the meta key does not exist.
	 */
	public static function get_post_meta_int_or_null( int $post_id, string $meta_key ): ?int {

		$value = self::get_post_meta_or_null( $post_id, $meta_key );

		if ( $value === null || $value === '' ) {
			return null;
		}

		return TypeCaster::to_int( $value );
	}

	/**
	 * Returns the post meta value as a string or null when the meta key does not exist.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID to read meta from.
	 * @param string $meta_key The meta key to read.
	 *
	 * @return string|null The stored string value, or null when the meta key does not exist.
	 */
	public static function get_post_meta_string_or_null( int $post_id, string $meta_key ): ?string {

		$value = self::get_post_meta_or_null( $post_id, $meta_key );

		if ( $value === null || $value === '' ) {
			return null;
		}

		return TypeCaster::to_string( $value );
	}
}
