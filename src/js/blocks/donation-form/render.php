<?php

declare( strict_types=1 );

$fundrik_markup = fundrik_get_donation_form();

if ( $fundrik_markup === '' ) {
	return;
}

printf(
	'<div %s>%s</div>',
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Wrapper attributes are normalized by WordPress.
	get_block_wrapper_attributes( [ 'class' => 'wp-block-fundrik-donation-form' ] ),
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is trusted output from the renderer.
	$fundrik_markup,
);
