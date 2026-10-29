<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use RuntimeException;

/**
 * Represents a donation repository failure in tests.
 */
final class FakeDonationRepositoryException extends RuntimeException implements DonationRepositoryExceptionInterface {}
