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

	#[Test]
	public function it_returns_route_metadata_for_the_donations_route(): void {

		$this->assertSame(
			RestRouteDefinitions::NAMESPACE_V1,
			RestRouteDefinitions::get_route_namespace( DonationsRestRoute::class ),
		);
		$this->assertSame(
			'/donations',
			RestRouteDefinitions::get_route_path( DonationsRestRoute::class ),
		);
		$this->assertSame(
			'fundrik/v1/donations',
			RestRouteDefinitions::get_route( DonationsRestRoute::class ),
		);
		$this->assertSame(
			'/fundrik/v1/donations',
			RestRouteDefinitions::get_request_path( DonationsRestRoute::class ),
		);
	}
}
