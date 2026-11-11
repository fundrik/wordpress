<?php

declare(strict_types=1);

define( 'FUNDRIK_PATH', dirname( __DIR__, 2 ) . '/' );
define( 'FUNDRIK_URL', 'https://example.test/wp-content/plugins/fundrik/' );
define( 'FUNDRIK_VERSION', '1.0.0' );

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress result format constant.
define( 'ARRAY_A', 'ARRAY_A' );

require_once __DIR__ . '/../../vendor/autoload.php';
