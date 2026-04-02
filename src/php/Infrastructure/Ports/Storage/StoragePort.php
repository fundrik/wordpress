<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Ports\Storage;

/**
 * Provides the outbound port for key-value storage access.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface StoragePort {

	/**
	 * Returns the stored value for the given key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key.
	 *
	 * @return mixed Stored value.
	 *
	 * @throws StorageNotFoundExceptionInterface When the key is not present.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function get( string $key ): mixed;

	/**
	 * Stores a value under the given key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Storage key.
	 * @param mixed $value Value to store.
	 *
	 * @throws StorageExceptionInterface When writing fails.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function set( string $key, mixed $value ): void;
}
