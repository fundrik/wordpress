<?php

declare(strict_types=1);

if ( class_exists( 'WP_REST_Server' ) ) {
	return;
}

/**
 * Minimal WP_REST_Server stub for unit tests.
 *
 * @internal
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, SlevomatCodingStandard.Files.TypeNameMatchesFileName.NoMatchBetweenTypeNameAndFileName
class WP_REST_Server {

	public const string CREATABLE = 'POST';
}
