<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi;

use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRoute;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( RestRouteDefinitions::class )]
final class RestRouteDefinitionsTest extends WordPressTestCase {

	#[Test]
	public function it_exposes_expected_rest_route_classes(): void {

		$this->assertSame(
			[
				DonationCheckoutRestRoute::class,
				YooKassaNotifyRestRoute::class,
			],
			RestRouteDefinitions::classes(),
		);
	}

	#[Test]
	public function it_returns_route_metadata_for_the_declared_routes(): void {

		$this->assertSame(
			RestRouteDefinitions::NAMESPACE_V1,
			DonationCheckoutRestRoute::get_route_namespace(),
		);
		$this->assertSame(
			'/checkout',
			DonationCheckoutRestRoute::get_route_path(),
		);
		$this->assertSame(
			'/fundrik/v1/checkout',
			RestRouteDefinitions::get_request_path( DonationCheckoutRestRoute::class ),
		);
		$this->assertSame(
			'http://example.test/wp-json/fundrik/v1/checkout',
			RestRouteDefinitions::get_route_url( DonationCheckoutRestRoute::class ),
		);
		$this->assertSame(
			RestRouteDefinitions::NAMESPACE_V1,
			YooKassaNotifyRestRoute::get_route_namespace(),
		);
		$this->assertSame(
			'/gateways/yookassa/notify',
			YooKassaNotifyRestRoute::get_route_path(),
		);
		$this->assertSame(
			'/fundrik/v1/gateways/yookassa/notify',
			RestRouteDefinitions::get_request_path( YooKassaNotifyRestRoute::class ),
		);
		$this->assertSame(
			'http://example.test/wp-json/fundrik/v1/gateways/yookassa/notify',
			RestRouteDefinitions::get_route_url( YooKassaNotifyRestRoute::class ),
		);
	}
}
