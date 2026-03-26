<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Infrastructure\Repositories\DonationRepository;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationAlreadyExistsExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Thrown when inserting a donation fails because the donation ID already exists.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class DonationAlreadyExistsException extends RuntimeException implements DonationAlreadyExistsExceptionInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $donation_id The duplicate donation ID.
	 * @param Throwable|null $previous The previous low-level exception.
	 */
	public function __construct( string $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			sprintf(
				'Cannot insert donation "%s": donation already exists.',
				$donation_id,
			),
			0,
			$previous,
		);
	}
}
