<?php

declare(strict_types=1);

use Fundrik\WordPress\Integration\Services\CampaignSummaryDisplayService;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;

/**
 * Returns the campaign summary markup for the given campaign or current campaign post.
 *
 * @since 1.0.0
 *
 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
 * @param array<string, mixed> $display_options Campaign summary display options.
 *
 * @return string Rendered campaign summary markup.
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fundrik_get_campaign_summary( ?int $campaign_id = null, array $display_options = [] ): string {

	return RuntimeContainer::get()
		->make( CampaignSummaryDisplayService::class )
		->render( $campaign_id, $display_options );
}

/**
 * Echoes the campaign summary markup for the given campaign or current campaign post.
 *
 * @since 1.0.0
 *
 * @param int|null $campaign_id Campaign ID, or null to use current campaign post.
 * @param array<string, mixed> $display_options Campaign summary display options.
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 */
function fundrik_the_campaign_summary( ?int $campaign_id = null, array $display_options = [] ): void {

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is trusted output from the renderer.
	echo fundrik_get_campaign_summary( $campaign_id, $display_options );
}
