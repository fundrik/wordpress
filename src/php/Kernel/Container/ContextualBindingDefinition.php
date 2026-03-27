<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Kernel\Container;

/**
 * Describes a contextual container binding definition.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class ContextualBindingDefinition {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param class-string $consumer The concrete type being resolved.
	 * @param class-string $dependency The dependency type or parameter name requested by the consumer.
	 * @param array<int, class-string> $implementation The implementation passed to Laravel's contextual `give()`.
	 *
	 * @phpstan-param list<class-string> $implementation
	 */
	public function __construct(
		public string $consumer,
		public string $dependency,
		public array $implementation,
	) {}
}
