<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;

/**
 * Provides the list of available post type config classes.
 *
 * @since 1.0.0
 *
 * @internal
 */
class PostTypeConfigRegistry {

	/**
	 * Returns all post type config class names.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> The list of post type config class names.
	 *
	 * @phpstan-return list<class-string<PostTypeConfigInterface>>
	 */
	public function get_post_type_config_classes(): array {

		return [
			CampaignPostTypeConfig::class,
		];
	}
}
