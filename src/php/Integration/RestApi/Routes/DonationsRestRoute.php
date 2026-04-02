<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;
use Fundrik\WordPress\Integration\WpSchemaType;
use Override;
use WP_REST_Server;

/**
 * Describes the public donations REST API route.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationsRestRoute implements RestRouteInterface {

	public const string ROUTE_NAMESPACE = RestRouteDefinitions::NAMESPACE_V1;
	public const string ROUTE_PATH = '/donations';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationRestRequestHandler $request_handler Handles donation creation requests.
	 */
	public function __construct(
		private CreateDonationRestRequestHandler $request_handler,
	) {}

	/**
	 * Returns the REST API namespace for the donation route.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function get_route_namespace(): string {

		return self::ROUTE_NAMESPACE;
	}

	/**
	 * Returns the REST API route path for donations.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function get_route_path(): string {

		return self::ROUTE_PATH;
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the WordPress registration arguments for the donation route.
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
				'args' => [
					'donation_id' => [
						'required' => true,
						'type' => WpSchemaType::String->value,
						'format' => 'uuid',
					],
					'campaign_id' => [
						'required' => true,
						'type' => WpSchemaType::Integer->value,
						'minimum' => 1,
					],
					'amount' => [
						'required' => true,
						'type' => WpSchemaType::Integer->value,
						'minimum' => 1,
					],
				],
			],
		];
	}
}
