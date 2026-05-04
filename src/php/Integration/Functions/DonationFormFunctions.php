<?php

declare(strict_types=1);

use Fundrik\WordPress\Integration\Services\DonationFormDisplayService;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;

/**
 * Returns the donation form markup for the given campaign or current campaign post.
 *
 * @since 1.0.0
 *
 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
 *
 * @return string Rendered donation form markup.
 */
function fundrik_get_donation_form( ?int $campaign_id = null ): string {

	return RuntimeContainer::get()->make( DonationFormDisplayService::class )->render( $campaign_id );
}

/**
 * Echoes the donation form markup for the given campaign or current campaign post.
 *
 * @since 1.0.0
 *
 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
 */
function fundrik_the_donation_form( ?int $campaign_id = null ): void {

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Donation form markup is escaped by the renderer.
	echo fundrik_get_donation_form( $campaign_id );
}
