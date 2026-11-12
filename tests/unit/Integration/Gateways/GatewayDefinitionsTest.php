<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Gateways;

use Fundrik\WordPress\Integration\Gateways\GatewayDefinitions;
use Fundrik\WordPress\Integration\Gateways\YooKassa\YooKassaGateway;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( GatewayDefinitions::class )]
final class GatewayDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function classes_returns_configured_gateway_classes(): void {

		self::assertSame(
			[
				YooKassaGateway::class,
			],
			GatewayDefinitions::classes(),
		);
	}
}
