<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi;

use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( RestRouteDefinitions::class )]
final class RestRouteDefinitionsTest extends FundrikTestCase {

	#[Test]
	public function it_exposes_expected_rest_route_classes(): void {

		$this->assertSame(
			[
				DonationsRestRoute::class,
			],
			RestRouteDefinitions::classes(),
		);
	}
}
