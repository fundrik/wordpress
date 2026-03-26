<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\DonationRepository;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationNotFoundExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Thrown when updating a donation fails because the donation ID does not exist.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class DonationNotFoundException extends RuntimeException implements DonationNotFoundExceptionInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $donation_id The missing donation ID.
	 * @param Throwable|null $previous The previous low-level exception.
	 */
	public function __construct( string $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			sprintf(
				'Cannot update donation "%s": persisted record not found.',
				$donation_id,
			),
			0,
			$previous,
		);
	}
}
