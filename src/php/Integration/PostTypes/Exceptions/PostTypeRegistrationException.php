<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\PostTypes\Exceptions;

use RuntimeException;

/**
 * Thrown when a post type cannot be registered in WordPress.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class PostTypeRegistrationException extends RuntimeException {}
