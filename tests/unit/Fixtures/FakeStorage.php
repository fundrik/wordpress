<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Override;

/**
 * Provides in-memory key-value storage for tests.
 */
final class FakeStorage implements StoragePort {

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $values Initial stored values.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function __construct(
		private array $values = [],
	) {}

	#[Override]
	public function get( string $key ): mixed {

		if ( ! array_key_exists( $key, $this->values ) ) {
			throw new FakeStorageNotFoundException(
				sprintf( 'Storage value "%s" was not found.', $key ),
			);
		}

		return $this->values[ $key ];
	}

	#[Override]
	public function set( string $key, mixed $value ): void {

		$this->values[ $key ] = $value;
	}
}
