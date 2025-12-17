<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Bootstrap\Container;

use RuntimeException;

/**
 * Thrown when a dependency cannot be resolved or instantiated by the container.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class ContainerException extends RuntimeException {}
