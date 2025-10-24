<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Container;

use RuntimeException;

/**
 * Thrown when a dependency cannot be resolved or instantiated by the container.
 *
 * @since 1.0.0
 */
final class ContainerException extends RuntimeException {}
