<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways;

use RuntimeException;

/**
 * Thrown when the selected gateway cannot be resolved.
 *
 * @since 1.0.0
 */
final class GatewayResolutionException extends RuntimeException {}
