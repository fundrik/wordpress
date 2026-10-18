<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes;

use Fundrik\Toolbox\TypeCaster;
use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostMetaRegistrationException;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostTypeRegistrationException;
use InvalidArgumentException;
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

		$labels = $post_type_config->get_labels();

		/**
		 * Filters the post type labels before registration.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $labels The post type labels.
		 */
		$filtered_labels = apply_filters( "fundrik_{$id}_post_type_labels", $labels );

		$labels = $this->ensure_labels_filter_result( $filtered_labels, $labels, $id );

		$slug = $post_type_config->get_slug();

		/**
		 * Filters the post type rewrite slug before registration.
		 *
		 * @since 1.0.0
		 *
		 * @param string $slug The post type slug.
		 */
		$filtered_slug = apply_filters( "fundrik_{$id}_post_type_slug", $slug );

		$slug = $this->ensure_slug_filter_result( $filtered_slug, $slug, $id );

		$args = [
			'labels' => $labels,
			'public' => true,
			'menu_icon' => 'dashicons-heart',
			'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
			'has_archive' => true,
			'rewrite' => [ 'slug' => $slug ],
			'show_in_menu' => AdminPageDefinitions::ROOT_MENU_SLUG,
			'show_in_rest' => true,
			'template' => $post_type_config->get_block_template(),
		];

		$result = register_post_type( $id, $args );

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
	 * Ensures the filtered post type labels have valid types.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $filtered_labels Filtered labels.
	 * @param array<string, string> $labels Original labels.
	 * @param string $post_type_id Post type ID.
	 *
	 * @return array<string, string> Filtered labels, or original labels when the result is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function ensure_labels_filter_result(
		mixed $filtered_labels,
		array $labels,
		string $post_type_id,
	): array {

		if ( ! $this->is_string_map( $filtered_labels ) ) {

			_doing_it_wrong(
				"fundrik_{$post_type_id}_post_type_labels",
				sprintf(
					'Filter must return an array of strings. Given: %s.',
					esc_html( get_debug_type( $filtered_labels ) ),
				),
				'1.0.0',
			);

			return $labels;
		}

		return $filtered_labels;
	}

	/**
	 * Checks whether a value is a string-keyed map of strings.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value to check.
	 *
	 * @return bool True when the value is a string-keyed map of strings.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function is_string_map( mixed $value ): bool {

		if ( ! is_array( $value ) ) {
			return false;
		}

		try {

			foreach ( $value as $key => $part ) {

				TypeCaster::to_string( $key );
				TypeCaster::to_string( $part );
			}
		} catch ( InvalidArgumentException ) {

			return false;
		}

		return true;
	}

	/**
	 * Ensures the filtered post type slug is a string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $filtered_slug Filtered slug.
	 * @param string $slug Original slug.
	 * @param string $post_type_id Post type ID.
	 *
	 * @return string Filtered slug, or original slug when the result is invalid.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private function ensure_slug_filter_result( mixed $filtered_slug, string $slug, string $post_type_id ): string {

		try {
			return TypeCaster::to_string( $filtered_slug );
		} catch ( InvalidArgumentException ) {

			_doing_it_wrong(
				"fundrik_{$post_type_id}_post_type_slug",
				sprintf(
					'Filter must return a string. Given: %s.',
					esc_html( get_debug_type( $filtered_slug ) ),
				),
				'1.0.0',
			);

			return $slug;
		}
	}

	/**
	 * Registers all meta fields for the given post type.
	 *
	 * @since 1.0.0
	 *
	 * @param PostTypeConfigInterface $post_type_config Provides the post type config.
	 *
	 * @throws PostMetaRegistrationException When meta field registration fails.
	 */
	private function register_post_meta_fields( PostTypeConfigInterface $post_type_config ): void {

		$post_type_id = $post_type_config->get_id();

		foreach ( $this->meta_reader->get_meta_fields( $post_type_config ) as $field ) {

			$result = register_post_meta(
				$post_type_id,
				$field->meta_key,
				$field->to_wp_args() + [
					'show_in_rest' => true,
					'single' => true,
				],
			);

			if ( ! $result ) {

				throw new PostMetaRegistrationException(
					sprintf(
						'Failed to register post meta "%s" for post type "%s".',
						$field->meta_key,
						$post_type_id,
					),
				);
			}
		}
	}
}
