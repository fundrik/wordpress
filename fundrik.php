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

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistry;
use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistrar;
use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistry;
use Fundrik\WordPress\Kernel\Container\ContainerFactory;
use Fundrik\WordPress\Kernel\Plugin;
use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Monolog\Handler\StreamHandler as MonologStreamHandler;
use Monolog\Level as MonologLevel;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord as MonologLogRecord;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\UidProcessor as MonologUidProcessor;
use Monolog\Processor\WebProcessor as MonologWebProcessor;
use Psr\Log\LoggerInterface;

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
	 */
	function fundrik_init(): void {

		$container = ( new ContainerFactory() )->create();

		$registrar = new ContainerBindingsRegistrar( new ContainerBindingsRegistry( new HookDispatcherRegistry() ) );
		$registrar->register_bindings_into_container( $container );

		$container->singleton(
			LoggerInterface::class,
			static function (): LoggerInterface {

				$logger = new MonologLogger( 'fundrik' );

				$logs_dir = FUNDRIK_PATH . '/logs';
				$debug_handler = new MonologStreamHandler( $logs_dir . '/fundrik-debug.json', level: MonologLevel::Debug );
				$info_handler = new MonologStreamHandler( $logs_dir . '/fundrik.json', level: MonologLevel::Info );

				$debug_handler->setFormatter( new MonologJsonFormatter() );
				$info_handler->setFormatter( new MonologJsonFormatter() );

				$logger->pushProcessor( new MonologUidProcessor() );
				$logger->pushProcessor( new IntrospectionProcessor() );

				$web = new MonologWebProcessor();
				$logger->pushProcessor(
					static function ( MonologLogRecord $record ) use ( $web ): MonologLogRecord {

						$record = $web( $record );

						unset( $record['extra']['server'] );

						$record['extra']['is_admin'] = function_exists( 'is_admin' ) ? is_admin() : null;
						$record['extra']['is_ajax'] = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : null;
						$record['extra']['is_cron'] = function_exists( 'wp_doing_cron' ) ? wp_doing_cron() : null;
						$record['extra']['is_json'] = function_exists( 'wp_is_json_request' ) ? wp_is_json_request() : null;
						$record['extra']['user_id'] = function_exists( 'get_current_user_id' ) ? get_current_user_id() : null;

						$start = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime( true );
						$record['extra']['duration_ms'] = (int) round( ( microtime( true ) - $start ) * 1_000 );
						$record['extra']['memory_peak_mb'] = round( memory_get_peak_usage( true ) / ( 1_024 * 1_024 ), 2 );

						return $record;
					},
				);

				$logger->pushHandler( $debug_handler );
				$logger->pushHandler( $info_handler );

				return $logger;
			},
		);

		$container->make( Plugin::class )->run();
	}
}

add_action( 'plugins_loaded', 'fundrik_init' );
