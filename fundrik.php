<?php

// phpcs:disable SlevomatCodingStandard.Commenting.ForbiddenAnnotations.AnnotationForbidden, SlevomatCodingStandard.Commenting.DocCommentSpacing.IncorrectLinesCountBetweenDifferentAnnotationsTypes
/**
 * The Fundrik plugin entry point.
 *
 * @author Denis Yanchevskiy
 * @copyright 2025
 * @license GPLv2+
 *
 * @since 1.0.0
 *
 * Plugin Name: Fundrik
 * Plugin URI: https://fundrik.ru
 * Description: Fundraising solution for WordPress
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Author: Denis Yanchevskiy
 * Author URI: https://denisco.pro
 * License: GPLv2 or later
 * Text Domain: fundrik
 */
// phpcs:enable


declare(strict_types=1);

use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistrar;
use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistry;
use Fundrik\WordPress\Kernel\Container\ContainerFactory;
use Fundrik\WordPress\Kernel\Plugin;

defined( 'ABSPATH' ) || die;

define( 'FUNDRIK_URL', plugin_dir_url( __FILE__ ) );
define( 'FUNDRIK_PATH', plugin_dir_path( __FILE__ ) );
define( 'FUNDRIK_BASENAME', plugin_basename( __FILE__ ) );
define( 'FUNDRIK_VERSION', '1.0.0' );

require_once FUNDRIK_PATH . 'vendor/autoload.php';

if ( ! function_exists( 'fundrik_init' ) ) {

	/**
	 * Initializes the Fundrik plugin.
	 *
	 * @since 1.0.0
	 *
	 * @internal
	 */
	function fundrik_init(): void {

		try {
			$container = ( new ContainerFactory() )->create();

			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			$registrar = new ContainerBindingsRegistrar( new ContainerBindingsRegistry() );
			$registrar->register_bindings_into_container( $container );

			$container->make( Plugin::class )->run();
		} catch ( Throwable $e ) {

			fundrik_set_failure_message( $e->getMessage() );

			if ( fundrik_is_debug_enabled() ) {

				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					sprintf(
						'Fundrik initialization failed: %s in %s:%d',
						$e->getMessage(),
						$e->getFile(),
						$e->getLine(),
					),
				);
			}

			return;
		}
	}
}

add_action( 'plugins_loaded', 'fundrik_init' );

/**
 * Renders an admin notice when the plugin failed during the current request.
 *
 * @since 1.0.0
 *
 * @internal
 */
function fundrik_render_failure_notice(): void {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
	$message = $GLOBALS['fundrik_failure_message'] ?? null;

	if ( empty( $message ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html( 'Fundrik is disabled because it failed to run.' );

	if ( fundrik_is_debug_enabled() ) {
		echo '<br><code>';
		echo esc_html( (string) $message );
		echo '</code>';
	}

	echo '</p></div>';
}

add_action( 'admin_notices', 'fundrik_render_failure_notice' );

/**
 * Checks whether WordPress debug mode is enabled.
 *
 * @since 1.0.0
 *
 * @internal
 */
function fundrik_is_debug_enabled(): bool {

	return defined( 'WP_DEBUG' ) && WP_DEBUG;
}

/**
 * Stores the failure message for the current request.
 *
 * @since 1.0.0
 *
 * @param string $message The message describing the failure.
 *
 * @internal
 */
function fundrik_set_failure_message( string $message ): void {

	// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
	$GLOBALS['fundrik_failure_message'] = $message;
}
