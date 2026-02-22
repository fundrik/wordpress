<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\SyncPostToCampaignBootUnit;

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
	public function get_boot_unit_classes(): array {

		return [
			FilterAllowedBlocksByPostTypeBootUnit::class,
			RegisterBlocksBootUnit::class,
			RegisterPostTypesBootUnit::class,
			SyncPostToCampaignBootUnit::class,
		];
	}
}
