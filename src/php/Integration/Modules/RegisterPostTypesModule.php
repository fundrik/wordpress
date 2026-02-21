<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Modules;

use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigFactory;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigRegistry;
use Fundrik\WordPress\Integration\PostTypes\PostTypeRegistrar;

/**
 * Registers all custom post types declared in the plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterPostTypesModule implements ModuleInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param InitActionHookDispatcher $init_hook Dispatches the WordPress 'init' action to attached listeners.
	 * @param PostTypeConfigRegistry $post_type_config_registry Provides the declared post type config classes.
	 * @param PostTypeConfigFactory $post_type_config_factory Creates post type config instances.
	 * @param PostTypeRegistrar $post_type_registrar Registers post types and post meta fields in WordPress.
	 */
	public function __construct(
		private InitActionHookDispatcher $init_hook,
		private PostTypeConfigRegistry $post_type_config_registry,
		private PostTypeConfigFactory $post_type_config_factory,
		private PostTypeRegistrar $post_type_registrar,
	) {}

	/**
	 * Attaches the post types registration callback to the WordPress 'init' action.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {

		$this->init_hook->attach( $this->register_post_types( ... ) );
	}

	/**
	 * Registers all declared post types in WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @throws InvalidArgumentException When a post type config class is invalid.
	 */
	private function register_post_types(): void {

		foreach ( $this->post_type_config_registry->get_post_type_config_classes() as $post_type_config_class ) {

			$post_type_config = $this->post_type_config_factory->create( $post_type_config_class );

			$this->post_type_registrar->register( $post_type_config );
		}
	}
}
