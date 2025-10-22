<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Container;

use Closure;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use RuntimeException;

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
	 * Ensures the created instance matches the expected type, otherwise throws.
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
	 * @throws BindingResolutionException Thrown when the container cannot resolve or instantiate the given identifier.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function make( string $id, array $parameters = [] ): object {

		$instance = $this->inner->make( $id, $parameters );

		if ( ! $instance instanceof $id ) {

			throw new RuntimeException(
				sprintf(
					'Container made instance of %s, but expected implementation of %s.',
					get_debug_type( $instance ),
					$id,
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
	 * @param string $abstract The class or interface name to bind.
	 * @param Closure|string|null $concrete The implementation or factory to bind, or null to use the abstract.
	 *
	 * @phpstan-param class-string $abstract
	 * @phpstan-param Closure|class-string|null $concrete
	 */
	public function singleton(
		// phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.abstractFound
		string $abstract,
		Closure|string|null $concrete = null,
	): void {

		$this->inner->singleton( $abstract, $concrete );
	}
}
