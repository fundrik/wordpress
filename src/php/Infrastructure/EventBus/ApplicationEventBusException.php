<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\EventBus;

use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use RuntimeException;

/**
 * Signals that publishing an application event failed.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class ApplicationEventBusException extends RuntimeException implements ApplicationEventBusExceptionInterface {}
