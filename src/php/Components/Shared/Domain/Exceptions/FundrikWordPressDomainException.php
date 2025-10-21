<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Shared\Domain\Exceptions;

use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;

/**
 * Serves as the base exception for WordPress domain errors.
 *
 * @since 1.0.0
 */
abstract class FundrikWordPressDomainException extends FundrikDomainException {}
