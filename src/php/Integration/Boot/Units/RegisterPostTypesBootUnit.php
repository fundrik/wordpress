<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Boot\Units;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeRegistrar;
use Override;
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
	 * The configured post type configs.
	 *
	 * @var array<int, PostTypeConfigInterface>
	 *
	 * @phpstan-var list<PostTypeConfigInterface>
	 */
	private array $post_type_configs;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param InitActionHookDispatcher $init_hook Dispatches the WordPress 'init' action to attached listeners.
	 * @param PostTypeRegistrar $post_type_registrar Registers post types and post meta fields in WordPress.
	 * @param BootUnitLogger $logger Writes structured log entries.
	 * @param PostTypeConfigInterface ...$post_type_configs The post type configs to register.
	 */
	public function __construct(
		private InitActionHookDispatcher $init_hook,
		private PostTypeRegistrar $post_type_registrar,
		private BootUnitLogger $logger,
		PostTypeConfigInterface ...$post_type_configs,
	) {

		$this->post_type_configs = $post_type_configs;

		$this->logger->set_boot_unit_class( self::class );
	}

	/**
	 * Attaches the post types registration callback to the WordPress 'init' action.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function boot(): void {

		$this->init_hook->attach( $this->register_post_types( ... ) );
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Registers all configured post types in WordPress.
	 *
	 * @since 1.0.0
	 */
	private function register_post_types(): void {

		$registered_post_type_ids = [];

		try {

			foreach ( $this->post_type_configs as $post_type_config ) {

				$this->post_type_registrar->register( $post_type_config );

				$registered_post_type_ids[] = $post_type_config->get_id();
			}
		} catch ( Throwable $e ) {

			$this->logger->log_error(
				'Post type registration failed.',
				[
					'registered_count' => count( $registered_post_type_ids ),
					'total_count' => count( $this->post_type_configs ),
					'exception' => $e,
				],
			);

			throw $e;
		}
	}
	// phpcs:enable
}
