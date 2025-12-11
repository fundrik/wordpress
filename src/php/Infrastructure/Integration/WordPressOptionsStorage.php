<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration;

use Fundrik\WordPress\Infrastructure\StorageInterface;

/**
 * Provides access to WordPress options storage.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WordPressOptionsStorage implements StorageInterface {

	/**
	 * Retrieves the stored value for the given key, or returns the default.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The option name.
	 * @param mixed $default_value The fallback if the option is not set. Use `null` to explicitly return null.
	 *
	 * @return mixed The stored value, or the fallback if not present.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function get( string $key, mixed $default_value = null ): mixed {

		if ( func_num_args() === 1 ) {
			return get_option( $key );
		}

		return get_option( $key, $default_value );
	}

	/**
	 * Stores a value under the given key in WordPress options.
	 *
	 * WordPress will automatically determine whether the option is autoloaded
	 * based on internal heuristics. If you need explicit control over autoloading,
	 * use a platform-specific implementation.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The option name.
	 * @param mixed $value The value to store.
	 *
	 * @return bool True if the value was updated successfully.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function set( string $key, mixed $value ): bool {

		return update_option( $key, $value );
	}
}
