<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi;

use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
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

	private const array ROUTES = [
		DonationsRestRoute::class => [
			'namespace' => self::NAMESPACE_V1,
			'path' => '/donations',
		],
	];

	/**
	 * Returns the configured REST route classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<RestRouteInterface>>
	 */
	public static function classes(): array {

		return array_keys( self::ROUTES );
	}

	/**
	 * Returns the REST route namespace for the given route class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route_class REST route class.
	 *
	 * @phpstan-param class-string<RestRouteInterface> $route_class
	 *
	 * @return string REST route namespace.
	 */
	public static function get_route_namespace( string $route_class ): string {

		return self::get_route_definition( $route_class )['namespace'];
	}

	/**
	 * Returns the REST route path for the given route class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route_class REST route class.
	 *
	 * @phpstan-param class-string<RestRouteInterface> $route_class
	 *
	 * @return string REST route path.
	 */
	public static function get_route_path( string $route_class ): string {

		return self::get_route_definition( $route_class )['path'];
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
	 */
	public static function get_route( string $route_class ): string {

		return self::get_route_namespace( $route_class ) . self::get_route_path( $route_class );
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
	 */
	public static function get_route_url( string $route_class ): string {

		return rest_url( self::get_route( $route_class ) );
	}

	/**
	 * Returns the configured REST route metadata for the given route class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route_class REST route class.
	 *
	 * @phpstan-param class-string<RestRouteInterface> $route_class
	 *
	 * @return array{namespace: string, path: string} REST route metadata.
	 */
	private static function get_route_definition( string $route_class ): array {

		$definition = self::ROUTES[ $route_class ] ?? null;

		if ( $definition !== null ) {
			return $definition;
		}

		throw new InvalidArgumentException(
			sprintf(
				'REST route metadata must be defined for route class "%s".',
				$route_class,
			),
		);
	}
}
