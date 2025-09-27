<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Campaigns\Application\Ports\Out;

use Throwable;

/**
 * Signals an error raised by a campaign repository implementation.
 *
 * @since 1.0.0
 */
interface CampaignRepositoryExceptionInterface extends Throwable {}
