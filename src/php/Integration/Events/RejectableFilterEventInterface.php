<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\Events;

use Fundrik\WordPress\Infrastructure\EventDispatcher\InfrastructureEventInterface;
use WP_Error;

/**
 * Provides methods for rejecting WordPress filter operations with a WP_Error result.
 *
 * Marks infrastructure events that represent WordPress filters capable of aborting
 * the operation by returning an error instead of a filtered value.
 *
 * @since 1.0.0
 *
 * @internal
 */
interface RejectableFilterEventInterface extends InfrastructureEventInterface {

	/**
	 * Rejects the REST insert/update by attaching the given error.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Error $error Describes why the operation must be rejected.
	 */
	public function reject( WP_Error $error ): void;

	/**
	 * Returns whether the operation was rejected.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the event contains a rejection error.
	 */
	public function is_rejected(): bool;

	/**
	 * Returns the rejection error, if any.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|null The rejection error or null when not rejected.
	 */
	public function get_rejection_error(): ?WP_Error;
}
