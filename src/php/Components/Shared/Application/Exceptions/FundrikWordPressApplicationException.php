<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Components\Shared\Application\Exceptions;

use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationException;

/**
 * Serves as the base exception for WordPress application errors.
 *
 * @since 1.0.0
 */
abstract class FundrikWordPressApplicationException extends FundrikApplicationException {}
