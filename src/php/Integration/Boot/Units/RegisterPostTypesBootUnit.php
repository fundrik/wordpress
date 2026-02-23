<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigFactory;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigRegistry;
use Fundrik\WordPress\Integration\PostTypes\PostTypeRegistrar;
use Throwable;

/**
 * Registers all custom post types declared in the plugin.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RegisterPostTypesBootUnit implements BootUnitInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param InitActionHookDispatcher $init_hook Dispatches the WordPress 'init' action to attached listeners.
	 * @param PostTypeConfigRegistry $post_type_config_registry Provides the declared post type config classes.
	 * @param PostTypeConfigFactory $post_type_config_factory Creates post type config instances.
	 * @param PostTypeRegistrar $post_type_registrar Registers post types and post meta fields in WordPress.
	 * @param BootUnitLogger $logger Writes structured log entries.
	 */
	public function __construct(
		private InitActionHookDispatcher $init_hook,
		private PostTypeConfigRegistry $post_type_config_registry,
		private PostTypeConfigFactory $post_type_config_factory,
		private PostTypeRegistrar $post_type_registrar,
		private BootUnitLogger $logger,
	) {

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches the post types registration callback to the WordPress 'init' action.
	 *
	 * @since 1.0.0
	 */
	public function boot(): void {

		$this->init_hook->attach( $this->register_post_types( ... ) );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers all declared post types in WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @throws InvalidArgumentException When a post type config class is invalid.
	 */
	private function register_post_types(): void {

		$classes = $this->post_type_config_registry->get_post_type_config_classes();

		$registered_post_type_ids = [];

		try {

			foreach ( $classes as $post_type_config_class ) {

				$post_type_config = $this->post_type_config_factory->create( $post_type_config_class );

				$this->post_type_registrar->register( $post_type_config );

				$registered_post_type_ids[] = $post_type_config->get_id();
			}
		} catch ( Throwable $e ) {

			$this->logger->log_error(
				'Post type registration failed.',
				[
					'registered_count' => count( $registered_post_type_ids ),
					'total_count' => count( $classes ),
					'exception' => $e,
				],
			);

			throw $e;
		}

		$this->logger->log_info(
			'Registering post types completed.',
			[
				'registered_count' => count( $registered_post_type_ids ),
				'total_count' => count( $classes ),
				'registered_post_type_ids' => $registered_post_type_ids,
			],
		);
	}
	// phpcs:enable
}
