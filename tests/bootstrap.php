<?php

declare(strict_types=1);

define( 'FUNDRIK_MAIN_FILE', realpath( __DIR__ . '/../fundrik.php' ) );

define( 'FUNDRIK_PATH', realpath( dirname( FUNDRIK_MAIN_FILE ) ) . '/' );

define( 'FUNDRIK_URL', 'http://example.test/wp-content/plugins/fundrik/' );

define( 'FUNDRIK_VERSION', '1.0.0' );

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define( 'ARRAY_A', 'ARRAY_A' );

require_once __DIR__ . '/stubs/wp-block-type-registry.php';
require_once __DIR__ . '/stubs/wp-error.php';
