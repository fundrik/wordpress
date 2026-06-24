<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\DonationReadRepository;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadExceptionInterface;
use RuntimeException;

/**
 * Thrown when a donation read operation fails.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class DonationReadException extends RuntimeException implements DonationReadExceptionInterface {}
