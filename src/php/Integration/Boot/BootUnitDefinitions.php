<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\ExposeDonationFormEditorSettingsBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\InitializeFundrikAdminBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\LogCreateDonationRestRequestFailuresBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RegisterRestApiRoutesBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\SyncPostToCampaignBootUnit;

/**
 * Provides boot unit declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class BootUnitDefinitions {

	/**
	 * Returns the configured boot unit classes.
	 *
	 * @since 1.0.0
	 *
	 * @return list<class-string<BootUnitInterface>>
	 */
	public static function classes(): array {

		return [
			ExposeDonationFormEditorSettingsBootUnit::class,
			FilterAllowedBlocksByPostTypeBootUnit::class,
			InitializeFundrikAdminBootUnit::class,
			LogCreateDonationRestRequestFailuresBootUnit::class,
			RegisterBlocksBootUnit::class,
			RegisterPostTypesBootUnit::class,
			RegisterRestApiRoutesBootUnit::class,
			SyncPostToCampaignBootUnit::class,
		];
	}
}
