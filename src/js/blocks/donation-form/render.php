<?php

declare(strict_types=1);

$fundrik_markup = fundrik_get_donation_form();

if ( $fundrik_markup === '' ) {
	return;
}

?>
<div class="wp-block-fundrik-donation-form">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside the renderer.
	echo $fundrik_markup;
	?>
</div>
