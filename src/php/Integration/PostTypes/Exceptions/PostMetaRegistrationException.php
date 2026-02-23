<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes\Exceptions;

use RuntimeException;

/**
 * Thrown when a post meta field cannot be registered in WordPress.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class PostMetaRegistrationException extends RuntimeException {}
