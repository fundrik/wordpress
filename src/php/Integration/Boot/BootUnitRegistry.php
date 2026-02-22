<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesUnit;
use Fundrik\WordPress\Integration\Boot\Units\SyncPostToCampaignUnit;

/**
 * Provides the list of boot unit classes.
 *
 * @since 1.0.0
 *
 * @internal
 */
class BootUnitRegistry {

	/**
	 * Returns the list of boot unit class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> The list of boot unit classes.
	 *
	 * @phpstan-return list<class-string<BootUnitInterface>>
	 */
	public function get_dispatcher_classes(): array {

		return [
			FilterAllowedBlocksByPostTypeUnit::class,
			RegisterBlocksUnit::class,
			RegisterPostTypesUnit::class,
			SyncPostToCampaignUnit::class,
		];
	}
}
