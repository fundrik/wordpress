<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;
use Override;
use RuntimeException;
use Throwable;
use WP_REST_Server;

/**
 * Registers all declared REST API routes on WordPress REST bootstrap.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterRestApiRoutesBootUnit implements BootUnitInterface {

	/**
	 * The configured REST API routes.
	 *
	 * @var list<RestRouteInterface>
	 */
	private array $rest_routes;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param RestApiInitActionHookDispatcher $rest_api_init_hook Dispatches the WordPress 'rest_api_init' action.
	 * @param BootUnitLogger $logger Writes structured log entries.
	 * @param RestRouteInterface ...$rest_routes The REST API routes to register.
	 */
	public function __construct(
		private RestApiInitActionHookDispatcher $rest_api_init_hook,
		private BootUnitLogger $logger,
		RestRouteInterface ...$rest_routes,
	) {

		$this->rest_routes = $rest_routes;

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches route registration callback to the REST API bootstrap hook.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->rest_api_init_hook->attach( $this->register_routes( ... ) );
	}

	/**
	 * Registers all declared REST API routes.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Server $server The active REST server.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	private function register_routes( WP_REST_Server $server ): void {

		$registered_route_count = 0;

		try {

			foreach ( $this->rest_routes as $route ) {

				$this->register_route( $route );
				++$registered_route_count;
			}
		} catch ( Throwable $e ) {

			$this->logger->log_error(
				'REST route registration failed.',
				[
					'registered_count' => $registered_route_count,
					'total_count' => count( $this->rest_routes ),
					'exception' => $e,
				],
			);

			throw $e;
		}
	}

	/**
	 * Registers the given REST API route.
	 *
	 * @since 1.0.0
	 *
	 * @param RestRouteInterface $route REST API route definition.
	 *
	 * @throws RuntimeException When WordPress rejects route registration.
	 */
	private function register_route( RestRouteInterface $route ): void {

		$route_namespace = $route->get_route_namespace();
		$route_path = $route->get_route_path();

		$result = register_rest_route(
			$route_namespace,
			$route_path,
			$route->get_route_args(),
		);

		if ( $result === false ) {

			throw new RuntimeException(
				sprintf(
					'Failed to register REST route "%s%s".',
					$route_namespace,
					$route_path,
				),
			);
		}
	}
}
