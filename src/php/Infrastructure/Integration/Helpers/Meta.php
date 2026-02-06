<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Helpers;

/**
 * Provides helpers for safely reading WordPress metadata.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class Meta {

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
	 * Normalizes a WordPress boolean meta value.
	 *
	 * Converts the WordPress empty string representation of false to '0'.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value The raw meta value.
	 *
	 * @return string The normalized value.
	 */
	public static function normalize_wp_bool_value( string $value ): string {

		if ( $value === '' ) {
			return '0';
		}

		return $value;
	}
}
