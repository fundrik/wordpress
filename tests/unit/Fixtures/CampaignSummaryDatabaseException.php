<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseExceptionInterface;
use RuntimeException;

/**
 * Represents a campaign summary database failure in tests.
 */
final class CampaignSummaryDatabaseException extends RuntimeException implements DatabaseExceptionInterface {}
