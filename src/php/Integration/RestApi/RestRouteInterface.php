<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi;

/**
 * Represents a WordPress REST API route definition.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface RestRouteInterface {

	/**
	 * Returns the REST API namespace.
	 *
	 * @since 1.0.0
	 */
	public function get_route_namespace(): string;

	/**
	 * Returns the REST API route path.
	 *
	 * @since 1.0.0
	 */
	public function get_route_path(): string;

	/**
	 * Returns the registration arguments passed to WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The REST route registration arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function get_route_args(): array;
}
