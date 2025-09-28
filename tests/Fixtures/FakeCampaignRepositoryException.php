<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Fixtures;

use Exception;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;

final class FakeCampaignRepositoryException extends Exception implements CampaignRepositoryExceptionInterface {}
