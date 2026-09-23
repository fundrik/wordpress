<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways;

use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGateway;

/**
 * Provides gateway declarations for internal container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class GatewayDefinitions {

	/**
	 * Returns the configured gateway classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<GatewayInterface>> Gateway classes.
	 */
	public static function classes(): array {

		return [
			YooKassaGateway::class,
		];
	}
}
