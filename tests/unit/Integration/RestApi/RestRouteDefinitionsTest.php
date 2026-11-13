<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRoute;
use Fundrik\WordPress\Tests\Fixtures\TestRestRoute;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( RestRouteDefinitions::class )]
final class RestRouteDefinitionsTest extends WordPressTestCase {

	#[Test]
	public function classes_returns_configured_route_classes(): void {

		self::assertSame(
			[
				DonationCheckoutRestRoute::class,
				YooKassaNotifyRestRoute::class,
			],
			RestRouteDefinitions::classes(),
		);
	}

	#[Test]
	public function get_request_path_returns_namespaced_route_path(): void {

		self::assertSame(
			'/test/v1/test',
			RestRouteDefinitions::get_request_path( TestRestRoute::class ),
		);
	}

	#[Test]
	public function get_route_url_returns_wordpress_rest_url(): void {

		Functions\expect( 'rest_url' )
			->once()
			->with( 'test/v1/test' )
			->andReturn( 'https://example.test/wp-json/test/v1/test' );

		self::assertSame(
			'https://example.test/wp-json/test/v1/test',
			RestRouteDefinitions::get_route_url( TestRestRoute::class ),
		);
	}
}
