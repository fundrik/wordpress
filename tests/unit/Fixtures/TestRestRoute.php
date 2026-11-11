<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;
use Override;

/**
 * Provides a configurable REST route for tests.
 */
final readonly class TestRestRoute implements RestRouteInterface {

	/**
	 * Creates the REST route.
	 *
	 * @param list<array<string, mixed>> $route_args Route registration arguments.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public function __construct(
		private array $route_args,
	) {}

	#[Override]
	public static function get_route_namespace(): string {

		return 'test/v1';
	}

	#[Override]
	public static function get_route_path(): string {

		return '/test';
	}

	#[Override]
	public function get_route_args(): array {

		return $this->route_args;
	}
}
