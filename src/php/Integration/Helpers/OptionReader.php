<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Helpers;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StorageNotFoundExceptionInterface;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Provides typed reading helpers for WordPress options.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class OptionReader {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param StoragePort $storage Reads raw option values from storage.
	 */
	public function __construct(
		private StoragePort $storage,
	) {}

	/**
	 * Finds the option value as an integer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Option name.
	 *
	 * @return int|null Option value, or null when the option does not exist.
	 *
	 * @throws UnexpectedValueException When the stored value cannot be cast to an integer.
	 */
	public function find_int_option( string $option_name ): ?int {

		$value = $this->find_option( $option_name );

		if ( $value === null ) {
			return null;
		}

		return $this->cast_option_value( $option_name, 'int', $value, TypeCaster::to_int( ... ) );
	}

	/**
	 * Finds the option value as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Option name.
	 *
	 * @return string|null Option value, or null when the option does not exist.
	 *
	 * @throws UnexpectedValueException When the stored value cannot be cast to a string.
	 */
	public function find_string_option( string $option_name ): ?string {

		$value = $this->find_option( $option_name );

		if ( $value === null ) {
			return null;
		}

		return $this->cast_option_value( $option_name, 'string', $value, TypeCaster::to_string( ... ) );
	}

	/**
	 * Finds the option value by name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Option name.
	 *
	 * @return mixed|null Stored option value, or null when the option does not exist.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function find_option( string $option_name ): mixed {

		try {
			return $this->storage->get( $option_name );
		} catch ( StorageNotFoundExceptionInterface ) {
			return null;
		}
	}

	/**
	 * Casts a stored option value and wraps cast failures with option context.
	 *
	 * @since 1.0.0
	 *
	 * @template T of bool|float|int|string
	 *
	 * @param string $option_name Option name.
	 * @param string $expected_type Expected scalar type.
	 * @param mixed $value Stored option value.
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
	private function cast_option_value(
		string $option_name,
		string $expected_type,
		mixed $value,
		callable $caster,
	): bool|float|int|string {

		try {
			return $caster( $value );
		} catch ( InvalidArgumentException $e ) {
			throw new UnexpectedValueException(
				sprintf(
					'Option "%s" must be %s. Given: %s.',
					$option_name,
					$expected_type,
					get_debug_type( $value ),
				),
				previous: $e,
			);
		}
	}
}
