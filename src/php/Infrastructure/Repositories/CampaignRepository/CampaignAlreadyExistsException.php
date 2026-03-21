<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignAlreadyExistsExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Thrown when inserting a campaign fails because the campaign ID already exists.
 *
 * @since 1.0.0
 */
final class CampaignAlreadyExistsException extends RuntimeException implements CampaignAlreadyExistsExceptionInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The duplicate campaign ID.
	 * @param Throwable|null $previous The previous low-level exception.
	 */
	public function __construct( int $campaign_id, ?Throwable $previous = null ) {

		parent::__construct(
			sprintf(
				'Cannot insert campaign "%d": campaign already exists.',
				$campaign_id,
			),
			0,
			$previous,
		);
	}
}
