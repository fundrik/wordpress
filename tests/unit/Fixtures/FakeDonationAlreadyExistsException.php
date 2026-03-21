<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationAlreadyExistsExceptionInterface;
use RuntimeException;

/**
 * Test-only donation repository exception for duplicate donation inserts.
 */
final class FakeDonationAlreadyExistsException extends RuntimeException implements DonationAlreadyExistsExceptionInterface {}
