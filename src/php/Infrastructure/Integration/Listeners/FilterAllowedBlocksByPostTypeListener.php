<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Integration\Listeners;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventListenerInterface;
use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Infrastructure\Integration\PostTypes\Attributes\PostTypeSpecificBlockReader;

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
final class FilterAllowedBlocksByPostTypeListener implements InfrastructureEventListenerInterface {

	/**
	 * The map of block names to the list of allowed post types.
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
	 * @param PostTypeIdReader $id_reader Extracts the post type ID from class attributes.
	 * @param PostTypeSpecificBlockReader $block_reader Extracts post type specific blocks from class attributes.
	 */
	public function __construct(
		private readonly PostTypeIdReader $id_reader,
		private readonly PostTypeSpecificBlockReader $block_reader,
	) {}

	/**
	 * Handles the given event.
	 *
	 * @since 1.0.0
	 *
	 * // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong, SlevomatCodingStandard.Commenting.DocCommentSpacing.IncorrectLinesCountBetweenDifferentAnnotationsTypes
	 * @param FilterAllowedBlockTypesEvent $event Carries the allowed block types list, editor context, and plugin context.
	 */
	public function handle( FilterAllowedBlockTypesEvent $event ): void {

		$allowed = $event->allowed;

		if ( $allowed === false ) {
			return;
		}

		$current_post_type = $event->editor_context->post?->post_type;

		if ( $current_post_type === null ) {
			return;
		}

		if ( $allowed === true ) {
			$allowed = array_keys( $event->context->get_registered_block_types() );
		}

		$this->set_block_allowed_post_types( $event->context->get_declared_post_type_classes() );

		$filtered = array_filter(
			$allowed,
			fn ( string $block_name ): bool => $this->is_block_allowed( $block_name, $current_post_type ),
		);

		$event->allowed = array_values( $filtered );
	}

	/**
	 * Sets the map of block names to their allowed post types.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string> $post_types The list of post type class names.
	 *
	 * @phpstan-param list<class-string> $post_types
	 */
	private function set_block_allowed_post_types( array $post_types ): void {

		$map = [];

		foreach ( $post_types as $post_type_class ) {

			$block_names = $this->block_reader->get_blocks( $post_type_class );
			$post_type_id = $this->id_reader->get_id( $post_type_class );

			foreach ( $block_names as $block_name ) {
				$map[ $block_name ][] = $post_type_id;
			}
		}

		$this->block_allowed_post_types = $map;
	}

	/**
	 * Checks if a block is allowed for the given post type.
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
	private function is_block_allowed( string $block_name, string $current_post_type ): bool {

		$map = $this->block_allowed_post_types;

		if ( ! isset( $map[ $block_name ] ) ) {
			return true;
		}

		return in_array( $current_post_type, $map[ $block_name ], true );
	}
}
