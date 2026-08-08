<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi;

use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRoute;
use InvalidArgumentException;

/**
 * Provides REST route metadata and declarations.
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
			DonationCheckoutRestRoute::class,
			YooKassaNotifyRestRoute::class,
		];
	}

	/**
	 * Returns the request path for the given route class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route_class REST route class.
	 *
	 * @phpstan-param class-string<RestRouteInterface> $route_class
	 *
	 * @return string Request path.
	 *
	 * @throws InvalidArgumentException When the route class does not implement RestRouteInterface.
	 */
	public static function get_request_path( string $route_class ): string {

		return '/' . self::get_route( $route_class );
	}

	/**
	 * Returns the REST route URL for the given route class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route_class REST route class.
	 *
	 * @phpstan-param class-string<RestRouteInterface> $route_class
	 *
	 * @return string REST route URL.
	 *
	 * @throws InvalidArgumentException When the route class does not implement RestRouteInterface.
	 */
	public static function get_route_url( string $route_class ): string {

		return rest_url( self::get_route( $route_class ) );
	}

	/**
	 * Returns the REST route string for the given route class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route_class REST route class.
	 *
	 * @phpstan-param class-string<RestRouteInterface> $route_class
	 *
	 * @return string REST route string.
	 *
	 * @throws InvalidArgumentException When the route class does not implement RestRouteInterface.
	 */
	private static function get_route( string $route_class ): string {

		if ( ! is_a( $route_class, RestRouteInterface::class, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'REST route metadata must be defined for route class "%s".',
					$route_class,
				),
			);
		}

		return $route_class::get_route_namespace() . $route_class::get_route_path();
	}
}
