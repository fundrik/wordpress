<?php

declare(strict_types=1);

$fundrik_wp_load_file = '/var/www/html/wp-load.php';

if ( ! file_exists( $fundrik_wp_load_file ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	fwrite( STDERR, "WordPress bootstrap file was not found.\n" );
	exit( 1 );
}

require_once $fundrik_wp_load_file;
