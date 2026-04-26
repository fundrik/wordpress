<?php

declare(strict_types=1);

use Fundrik\WordPress\Integration\BlockRenderers\DonationFormBlockRenderer;

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is escaped inside the renderer.
echo ( new DonationFormBlockRenderer() )->render();
