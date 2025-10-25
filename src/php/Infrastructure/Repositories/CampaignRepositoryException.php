<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories;

use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use RuntimeException;

/**
 * Thrown when campaign persistence operation fails.
 *
 * @since 1.0.0
 */
final class CampaignRepositoryException extends RuntimeException implements CampaignRepositoryExceptionInterface {}
