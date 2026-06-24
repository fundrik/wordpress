<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages\Pages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Override;

/**
 * Represents the Fundrik settings admin page.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class SettingsAdminPage implements AdminPageInterface {

	/**
	 * Registers the Fundrik settings submenu.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_submenu_page(
			AdminPageDefinitions::ROOT_MENU_SLUG,
			__( 'Fundrik Settings', 'fundrik' ),
			__( 'Settings', 'fundrik' ),
			AdminPageDefinitions::SETTINGS_CAPABILITY,
			AdminPageDefinitions::SETTINGS_PAGE_ID,
			$this->render( ... ),
		);
	}

	/**
	 * Renders the Fundrik settings page.
	 *
	 * @since 1.0.0
	 */
	private function render(): void {

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fundrik Settings', 'fundrik' ); ?></h1>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( AdminPageDefinitions::SETTINGS_PAGE_ID );
				do_settings_sections( AdminPageDefinitions::ROOT_MENU_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
