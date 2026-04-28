<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Donations\Domain\Exceptions;

use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;

/**
 * Serves as the base exception for donation domain errors.
 *
 * @since 1.0.0
 *
 * @internal
 */
abstract class DonationDomainException extends FundrikDomainException {}
