<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;

final class DummyRestRoute implements RestRouteInterface {

	public static function get_route_namespace(): string {

		return 'fundrik/v1';
	}

	public static function get_route_path(): string {

		return '/dummy';
	}

	public function get_route_args(): array {

		return [];
	}
}
