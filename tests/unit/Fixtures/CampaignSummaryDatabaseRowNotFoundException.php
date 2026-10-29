<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Fundrik\WordPress\Infrastructure\Ports\Database\DatabaseRowNotFoundExceptionInterface;
use RuntimeException;

/**
 * Represents a missing campaign row in tests.
 */
final class CampaignSummaryDatabaseRowNotFoundException extends RuntimeException implements DatabaseRowNotFoundExceptionInterface {}
