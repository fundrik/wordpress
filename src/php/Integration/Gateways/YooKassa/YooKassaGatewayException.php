<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Gateways\YooKassa;

use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayExceptionInterface;
use RuntimeException;

/**
 * Thrown when YooKassa gateway checkout creation fails.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class YooKassaGatewayException extends RuntimeException implements DonationGatewayExceptionInterface {}
