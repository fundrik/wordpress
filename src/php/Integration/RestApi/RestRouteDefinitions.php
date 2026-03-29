<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi;

use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;

/**
 * Provides REST route declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RestRouteDefinitions {

	public const string NAMESPACE_V1 = 'fundrik/v1';

	/**
	 * Returns the configured REST route classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<RestRouteInterface>>
	 */
	public static function classes(): array {

		return [
			DonationsRestRoute::class,
		];
	}
}
