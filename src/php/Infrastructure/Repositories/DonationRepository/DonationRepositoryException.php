<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\DonationRepository;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use RuntimeException;

/**
 * Thrown when donation persistence operation fails.
 *
 * @since 1.0.0
 */
final class DonationRepositoryException extends RuntimeException implements DonationRepositoryExceptionInterface {}
