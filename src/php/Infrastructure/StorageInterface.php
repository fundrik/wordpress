<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure;

/**
 * Provides methods for accessing persistent key-value storage.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface StorageInterface {

	/**
	 * Retrieves the stored value for the given key, or returns the default.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The key to look up.
	 * @param mixed $default_value The fallback value if the key is not found.
	 *
	 * @return mixed The stored value, or the fallback if not present.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function get( string $key, mixed $default_value = null ): mixed;

	/**
	 * Stores a value under the given key.
	 *
	 * Returns false if the value did not change or the operation failed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The key to assign.
	 * @param mixed $value The value to store.
	 *
	 * @return bool True if the value was changed successfully.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function set( string $key, mixed $value ): bool;
}
