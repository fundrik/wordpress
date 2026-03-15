<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;

/**
 * Provides post type config declarations for container configuration.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeConfigDefinitions {

	/**
	 * Returns the configured post type config classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 *
	 * @phpstan-return list<class-string<PostTypeConfigInterface>>
	 */
	public static function classes(): array {

		return [
			CampaignPostTypeConfig::class,
		];
	}
}
