<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Storage;

use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Override;
use stdClass;

/**
 * Provides access to WordPress options storage.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class WordPressOptionsStorage implements StoragePort {

	/**
	 * Returns the stored option value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Option key.
	 *
	 * @return mixed Stored option value.
	 *
	 * @throws WordPressOptionNotFoundException When the option does not exist.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function get( string $key ): mixed {

		$missing = new stdClass();
		$value = get_option( $key, $missing );

		if ( $value === $missing ) {
			throw new WordPressOptionNotFoundException( $key );
		}

		return $value;
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
	 * @param string $key Option key.
	 * @param mixed $value Value to store.
	 *
	 * @throws WordPressOptionsStorageException When the value cannot be stored.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function set( string $key, mixed $value ): void {

		$missing = new stdClass();
		$current_value = get_option( $key, $missing );

		if ( update_option( $key, $value ) ) {
			return;
		}

		if ( $current_value !== $missing && $current_value === $value ) {
			return;
		}

		throw new WordPressOptionsStorageException(
			sprintf( 'Failed to write option "%s".', $key ),
		);
	}
}
