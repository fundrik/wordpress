<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;

/**
 * Resolves and registers bindings through the Laravel container.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class Container implements ContainerInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param LaravelContainerInterface $inner Resolves and binds dependencies using Laravel's container.
	 */
	public function __construct(
		private LaravelContainerInterface $inner,
	) {}

	/**
	 * Instantiates a class or interface, optionally with constructor parameters.
	 *
	 * Ensures the created instance matches the expected type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The class or interface name to instantiate.
	 * @param array<string, mixed> $parameters Optional constructor parameters.
	 *
	 * @template T of object
	 *
	 * @phpstan-param class-string<T> $id
	 *
	 * @phpstan-return T
	 *
	 * @return object The newly created instance matching the expected type.
	 *
	 * @throws ContainerException When the container cannot resolve or instantiate the given identifier.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function make( string $id, array $parameters = [] ): object {

		try {
			$instance = $this->inner->make( $id, $parameters );
		} catch ( BindingResolutionException $e ) {

			throw new ContainerException(
				sprintf( 'Failed to resolve dependency "%s".', $id ),
				previous: $e,
			);
		}

		if ( ! $instance instanceof $id ) {

			throw new ContainerException(
				sprintf(
					'Resolved service must be an instance of %s. Given: %s.',
					$id,
					get_debug_type( $instance ),
				),
			);
		}

		// phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable, Generic.Commenting.DocComment.MissingShort
		/** @var T $instance */
		return $instance;
	}

	/**
	 * Registers a binding into the container that is resolved fresh each time it is requested.
	 *
	 * - If $concrete is `null`, the container instantiates `$abstract` directly.
	 * - If $concrete is a `string`, the container resolves it when `$abstract` is requested.
	 * - If $concrete is a `Closure`, the container executes it to produce a new instance.
	 *
	 * @since 1.0.0
	 *
	 * @template T of object
	 *
	 * @phpstan-param class-string<T> $abstract
	 * @phpstan-param (Closure(): T)|class-string<T>|null $concrete
	 *
	 * @param string $abstract The class or interface name to bind.
	 * @param Closure|string|null $concrete The implementation or factory to bind, or null to use the abstract.
	 */
	public function bind(
		// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.abstractFound
		string $abstract,
		Closure|string|null $concrete = null,
	): void {

		$this->inner->bind( $abstract, $concrete );
	}

	/**
	 * Registers a singleton binding into the container.
	 *
	 * - If $concrete is `null`, the container instantiates `$abstract` directly.
	 * - If $concrete is a `string`, the container resolves it when `$abstract` is requested.
	 * - If $concrete is a `Closure`, the result is cached and reused.
	 *
	 * @since 1.0.0
	 *
	 * @template T of object
	 *
	 * @phpstan-param class-string<T> $abstract
	 * @phpstan-param (Closure(): T)|class-string<T>|null $concrete
	 *
	 * @param string $abstract The class or interface name to bind.
	 * @param Closure|string|null $concrete The implementation or factory to bind, or null to use the abstract.
	 */
	public function singleton(
		// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.abstractFound
		string $abstract,
		Closure|string|null $concrete = null,
	): void {

		$this->inner->singleton( $abstract, $concrete );
	}

	/**
	 * Registers an existing instance as a singleton binding.
	 *
	 * @since 1.0.0
	 *
	 * @template T of object
	 *
	 * @phpstan-param class-string<T> $abstract
	 * @phpstan-param T $instance
	 *
	 * @phpstan-return T
	 *
	 * @param string $abstract The class or interface name to bind.
	 * @param object $instance The existing instance.
	 *
	 * @throws ContainerException When the instance does not match the abstract type.
	 */
	public function instance(
		// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.abstractFound
		string $abstract,
		object $instance,
	): object {

		if ( ! $instance instanceof $abstract ) {

			throw new ContainerException(
				sprintf(
					'Registered instance must be an instance of %s. Given: %s.',
					$abstract,
					get_debug_type( $instance ),
				),
			);
		}

		$this->inner->instance( $abstract, $instance );

		return $instance;
	}
}
