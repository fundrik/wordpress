<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;
use Override;
use WP_REST_Server;

/**
 * Describes the YooKassa notification REST API route.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class YooKassaNotifyRestRoute implements RestRouteInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param YooKassaNotifyRestRequestHandler $request_handler Handles notification requests.
	 */
	public function __construct(
		private YooKassaNotifyRestRequestHandler $request_handler,
	) {}

	/**
	 * Returns the REST route namespace.
	 *
	 * @since 1.0.0
	 *
	 * @return string REST route namespace.
	 */
	#[Override]
	public static function get_route_namespace(): string {

		return RestRouteDefinitions::NAMESPACE_V1;
	}

	/**
	 * Returns the REST route path.
	 *
	 * @since 1.0.0
	 *
	 * @return string REST route path.
	 */
	#[Override]
	public static function get_route_path(): string {

		return '/gateways/yookassa/notify';
	}

	/**
	 * Returns the WordPress registration arguments for the notification route.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The registration arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function get_route_args(): array {

		return [
			[
				'methods' => WP_REST_Server::CREATABLE,
				'permission_callback' => __return_true( ... ),
				'callback' => $this->request_handler->handle( ... ),
			],
		];
	}
}
