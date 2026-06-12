<?php

declare(strict_types=1);

$fundrik_attributes = isset( $attributes ) && is_array( $attributes ) ? $attributes : [];
$fundrik_markup = fundrik_get_campaign_summary( display_options: $fundrik_attributes );
$fundrik_wrapper_attributes = get_block_wrapper_attributes( [ 'class' => 'wp-block-fundrik-campaign-summary' ] );

if ( $fundrik_markup === '' ) {
	return;
}

?>
<div <?php echo $fundrik_wrapper_attributes; ?>>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside the renderer.
	echo $fundrik_markup;
	?>
</div>
