<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;

final class DummyRestRoute implements RestRouteInterface {

	public function get_route_args(): array {

		return [];
	}
}
