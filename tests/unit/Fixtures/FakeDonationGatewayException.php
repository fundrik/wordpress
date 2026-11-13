<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayExceptionInterface;
use RuntimeException;

/**
 * Represents a donation gateway failure for tests.
 */
final class FakeDonationGatewayException extends RuntimeException implements DonationGatewayExceptionInterface {}
