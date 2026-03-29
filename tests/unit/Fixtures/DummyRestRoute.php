<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;

final class DummyRestRoute implements RestRouteInterface {

	public function get_route_namespace(): string {

		return RestRouteDefinitions::NAMESPACE_V1;
	}

	public function get_route_path(): string {

		return '/dummy';
	}

	public function get_route_args(): array {

		return [];
	}
}
