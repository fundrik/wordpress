<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\RestApi\Routes;

use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;
use Fundrik\WordPress\Integration\WpSchemaType;
use Override;
use WP_REST_Server;

/**
 * Describes the donation checkout REST API route.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationCheckoutRestRoute implements RestRouteInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CreateDonationCheckoutRestRequestHandler $request_handler Handles checkout creation requests.
	 */
	public function __construct(
		private CreateDonationCheckoutRestRequestHandler $request_handler,
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

		return '/checkout';
	}

	/**
	 * Returns the WordPress registration arguments for the checkout route.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> The registration arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function get_route_args(): array {

		return [ $this->get_checkout_route_arg() ];
	}

	/**
	 * Returns the checkout route registration arguments.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Route arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function get_checkout_route_arg(): array {

		return [
			'methods' => WP_REST_Server::CREATABLE,
			'permission_callback' => __return_true( ... ),
			'callback' => $this->request_handler->handle( ... ),
			'args' => $this->get_checkout_route_args(),
		];
	}

	/**
	 * Returns the checkout route request arguments.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Route request arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function get_checkout_route_args(): array {

		return [
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
		];
	}
}
