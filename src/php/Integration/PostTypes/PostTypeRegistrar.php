<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostMetaRegistrationException;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostTypeRegistrationException;
use WP_Error;

/**
 * Registers the post type and its meta fields in WordPress.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PostTypeRegistrar {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param PostTypeMetaFieldReader $meta_reader Extracts declared post meta fields from attributes.
	 */
	public function __construct(
		private PostTypeMetaFieldReader $meta_reader,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers the given post type in WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param PostTypeConfigInterface $post_type_config Provides the post type config.
	 *
	 * @throws PostTypeRegistrationException When post type registration fails.
	 * @throws PostMetaRegistrationException When meta field registration fails.
	 */
	public function register( PostTypeConfigInterface $post_type_config ): void {

		$id = $post_type_config->get_id();

		/**
		 * Filters the post type labels before registration.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $labels The post type labels.
		 *
		 * @return array<string, string> The filtered labels.
		 */
		$labels = apply_filters( "fundrik_{$id}_post_type_labels", $post_type_config->get_labels() );

		/**
		 * Filters the post type rewrite slug before registration.
		 *
		 * @since 1.0.0
		 *
		 * @param string $slug The post type slug.
		 *
		 * @return string The filtered slug.
		 */
		$slug = apply_filters( "fundrik_{$id}_post_type_slug", $post_type_config->get_slug() );

		$result = register_post_type(
			$id,
			[
				'labels' => $labels,
				'public' => true,
				'menu_icon' => 'dashicons-heart',
				'supports' => [ 'title', 'editor', 'custom-fields' ],
				'has_archive' => true,
				'rewrite' => [ 'slug' => $slug ],
				'show_in_rest' => true,
				'template' => $post_type_config->get_block_template(),
			],
		);

		if ( $result instanceof WP_Error ) {

			throw new PostTypeRegistrationException(
				sprintf(
					'Cannot register post type "%s": %s.',
					$id,
					$result->get_error_message(),
				),
			);
		}

		$this->register_post_meta_fields( $post_type_config );
	}
	// phpcs:enable

	/**
	 * Registers all meta fields for the given post type.
	 *
	 * @since 1.0.0
	 *
	 * @param PostTypeConfigInterface $post_type_config Provides the post type config.
	 */
	private function register_post_meta_fields( PostTypeConfigInterface $post_type_config ): void {

		$post_type_id = $post_type_config->get_id();

		foreach ( $this->meta_reader->get_meta_fields( $post_type_config ) as $meta_key => $args ) {

			$result = register_post_meta(
				$post_type_id,
				$meta_key,
				$args + [
					'show_in_rest' => true,
					'single' => true,
				],
			);

			if ( ! $result ) {

				throw new PostMetaRegistrationException(
					sprintf(
						'Failed to register post meta "%s" for post type "%s".',
						$meta_key,
						$post_type_id,
					),
				);
			}
		}
	}
}
