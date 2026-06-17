<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;
use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\BlockCategoriesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Override;
use WP_Block_Editor_Context;

/**
 * Registers all custom blocks declared in the plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterBlocksBootUnit implements BootUnitInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param InitActionHookDispatcher $init_hook Dispatches the WordPress 'init' action to attached listeners.
	 * @param BlockCategoriesAllFilterHookDispatcher $block_categories_hook Dispatches the block categories filter
	 *                                                                      to attached listeners.
	 * @param BootUnitLogger $logger Writes structured boot-unit logs.
	 */
	public function __construct(
		private InitActionHookDispatcher $init_hook,
		private BlockCategoriesAllFilterHookDispatcher $block_categories_hook,
		private BootUnitLogger $logger,
	) {

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches the block registration callback to the WordPress 'init' action.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->init_hook->attach( $this->register_blocks( ... ) );
		$this->block_categories_hook->attach( $this->register_block_category( ... ) );
	}

	/**
	 * Registers the block types from the metadata collection.
	 *
	 * @since 1.0.0
	 */
	private function register_blocks(): void {

		$blocks_path = PluginPath::Blocks->get_full_path();
		$manifest_path = PluginPath::BlocksManifest->get_full_path();

		wp_register_block_types_from_metadata_collection( $blocks_path, $manifest_path );
	}

	/**
	 * Registers the Fundrik block category.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array<string, mixed>> $categories Block categories.
	 * @param WP_Block_Editor_Context $editor_context Block editor context.
	 *
	 * @return array<int, array<string, mixed>> Block categories with Fundrik appended.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
	 */
	private function register_block_category( array $categories, WP_Block_Editor_Context $editor_context ): array {

		foreach ( $categories as $category ) {

			if ( ( $category['slug'] ?? null ) === 'fundrik' ) {
				return $categories;
			}
		}

		$categories[] = [
			'slug' => 'fundrik',
			'title' => __( 'Fundrik', 'fundrik' ),
		];

		return $categories;
	}
}
