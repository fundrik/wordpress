<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages\Pages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Override;

/**
 * Represents the Fundrik root admin page.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class RootAdminPage implements AdminPageInterface {

	private const string ICON = 'dashicons-heart';

	/**
	 * Registers the Fundrik root menu.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_menu_page(
			__( 'Fundrik', 'fundrik' ),
			__( 'Fundrik', 'fundrik' ),
			AdminPageDefinitions::CONTENT_CAPABILITY,
			AdminPageDefinitions::ROOT_MENU_SLUG,
			$this->render( ... ),
			self::ICON,
		);
	}

	/**
	 * Renders the Fundrik root page.
	 *
	 * @since 1.0.0
	 */
	private function render(): void {

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fundrik', 'fundrik' ); ?></h1>
			<p><?php esc_html_e( 'Use the submenu to open campaigns, donations, or settings.', 'fundrik' ); ?></p>
		</div>
		<?php
	}
}
