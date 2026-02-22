<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigFactory;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigRegistry;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use WP_Block_Editor_Context;

/**
 * Filters the allowed block types based on the current post type.
 *
 * Only blocks explicitly registered for a given post type will be allowed.
 * Unrestricted blocks remain allowed by default.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class FilterAllowedBlocksByPostTypeBootUnit implements BootUnitInterface {

	/**
	 * The map of block names to allowed post types.
	 *
	 * Format: [ block_name => [ post_type_id1, post_type_id2, ... ] ]
	 *
	 * @var array<string, array<string>>
	 */
	private array $block_allowed_post_types;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.Commenting.DocCommentSpacing.IncorrectLinesCountBetweenDifferentAnnotationsTypes
	 * @param AllowedBlockTypesAllFilterHookDispatcher $allowed_block_types_hook Dispatches the WordPress 'allowed_block_types_all' filter.
	 * @param WordPressContextInterface $wp_context Provides access to registered WordPress types.
	 * @param PostTypeConfigRegistry $post_type_config_registry Provides the declared post type config classes.
	 * @param PostTypeConfigFactory $post_type_config_factory Creates post type config instances.
	 */
	public function __construct(
		private AllowedBlockTypesAllFilterHookDispatcher $allowed_block_types_hook,
		private WordPressContextInterface $wp_context,
		private PostTypeConfigRegistry $post_type_config_registry,
		private PostTypeConfigFactory $post_type_config_factory,
	) {}

	/**
	 * Attaches the filter and prepares the block restriction map.
	 *
	 * @since 1.0.0
	 *
	 * @throws InvalidArgumentException When a post type config class is invalid.
	 */
	public function boot(): void {

		$this->block_allowed_post_types = $this->build_block_allowed_post_types_map();

		$this->allowed_block_types_hook->attach(
			$this->filter_allowed_block_types( ... ),
		);
	}

	/**
	 * Filters the allowed blocks list based on the current post type.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|array<string> $allowed The allowed blocks list, or true/false.
	 * @param WP_Block_Editor_Context $editor_context Provides the editor context, including the current post.
	 *
	 * @return bool|array<string> The filtered allowed blocks list, or true/false.
	 */
	private function filter_allowed_block_types(
		bool|array $allowed,
		WP_Block_Editor_Context $editor_context,
	): bool|array {

		if ( $allowed === false ) {
			return false;
		}

		$current_post_type = $editor_context->post?->post_type;

		if ( $current_post_type === null ) {
			return $allowed;
		}

		if ( $allowed === true ) {
			$allowed = array_keys( $this->wp_context->get_registered_block_types() );
		}

		$filtered = array_filter(
			$allowed,
			fn ( string $block_name ): bool => $this->is_block_allowed( $block_name, $current_post_type ),
		);

		return array_values( $filtered );
	}

	/**
	 * Builds the map of block names to their allowed post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string>> The map of block names to allowed post types.
	 *
	 * @throws InvalidArgumentException When a post type config class is invalid.
	 */
	private function build_block_allowed_post_types_map(): array {

		$map = [];

		foreach ( $this->create_post_type_configs() as $post_type_config ) {

			$post_type_id = $post_type_config->get_id();

			foreach ( $post_type_config->get_specific_blocks() as $block_name ) {
				$map[ $block_name ][] = $post_type_id;
			}
		}

		return $map;
	}

	/**
	 * Creates all declared post type configs.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, PostTypeConfigInterface> The list of post type config instances.
	 *
	 * @phpstan-return list<PostTypeConfigInterface>
	 *
	 * @throws InvalidArgumentException When a post type config class is invalid.
	 */
	private function create_post_type_configs(): array {

		$configs = [];

		foreach ( $this->post_type_config_registry->get_post_type_config_classes() as $class_name ) {
			$configs[] = $this->post_type_config_factory->create( $class_name );
		}

		return $configs;
	}

	/**
	 * Checks whether the block is allowed for the given post type.
	 *
	 * If the block is not explicitly restricted, it is allowed by default.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_name The block name.
	 * @param string $current_post_type The current post type slug.
	 *
	 * @return bool True if allowed.
	 */
	private function is_block_allowed( string $block_name, string $current_post_type, ): bool {

		if ( ! isset( $this->block_allowed_post_types[ $block_name ] ) ) {
			return true;
		}

		return in_array( $current_post_type, $this->block_allowed_post_types[ $block_name ], true );
	}
}
