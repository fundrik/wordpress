<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Ports\Database;

/**
 * Marks database write failures caused by a missing row.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface DatabaseRowNotFoundExceptionInterface extends DatabaseExceptionInterface {}
