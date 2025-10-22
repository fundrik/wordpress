<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Container;

use Closure;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Provides methods for instantiating, and binding dependencies.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface ContainerInterface {

	/**
	 * Instantiates a class or interface, optionally with constructor parameters.
	 *
	 * Ensures the created instance matches the expected type, otherwise throws.
	 *
	 * @since 1.0.0
	 *
	 * @template T of object
	 *
	 * @phpstan-param class-string<T> $id
	 *
	 * @phpstan-return T
	 *
	 * @param string $id The class or interface name to instantiate.
	 * @param array<string, mixed> $parameters Optional constructor parameters.
	 *
	 * @return object The newly created instance matching the expected type.
	 *
	 * @throws BindingResolutionException Thrown when the container cannot resolve or instantiate the given identifier.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function make( string $id, array $parameters = [] ): object;

	/**
	 * Registers a binding into the container that is resolved fresh each time it is requested.
	 *
	 * - If $concrete is `null`, the container instantiates `$abstract` directly.
	 * - If $concrete is a `string`, the container resolves it when `$abstract` is requested.
	 * - If $concrete is a `Closure`, the container executes it to produce a new instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $abstract The class or interface name to bind.
	 * @param Closure|string|null $concrete The implementation or factory to bind, or null to use the abstract.
	 *
	 * @phpstan-param class-string $abstract
	 * @phpstan-param Closure|class-string|null $concrete
	 */
	public function bind(
		// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.abstractFound
		string $abstract,
		Closure|string|null $concrete = null,
	): void;

	/**
	 * Registers a singleton binding into the container.
	 *
	 * - If $concrete is `null`, the container instantiates `$abstract` directly.
	 * - If $concrete is a `string`, the container resolves it when `$abstract` is requested.
	 * - If $concrete is a `Closure`, the result is cached and reused.
	 *
	 * @since 1.0.0
	 *
	 * @phpstan-param class-string $abstract
	 * @phpstan-param Closure|class-string|null $concrete
	 *
	 * @param string $abstract The class or interface name to bind.
	 * @param Closure|string|null $concrete The implementation or factory to bind, or null to use the abstract.
	 */
	public function singleton(
		// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.abstractFound
		string $abstract,
		Closure|string|null $concrete = null,
	): void;
}
