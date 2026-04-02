<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Ports\Storage;

/**
 * Marks storage exceptions raised when a key is not present.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface StorageNotFoundExceptionInterface extends StorageExceptionInterface {}
