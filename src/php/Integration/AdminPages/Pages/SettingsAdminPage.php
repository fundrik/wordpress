<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages\Pages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Override;

/**
 * Represents the Fundrik settings admin page.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class SettingsAdminPage implements AdminPageInterface {

	private const string CAPABILITY = 'edit_posts';

	private const string ICON = 'dashicons-heart';

	/**
	 * Registers the Fundrik root menu and settings submenu.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_menu_page(
			__( 'Fundrik Settings', 'fundrik' ),
			__( 'Fundrik', 'fundrik' ),
			self::CAPABILITY,
			AdminPageDefinitions::ROOT_MENU_SLUG,
			$this->render( ... ),
			self::ICON,
		);

		add_submenu_page(
			AdminPageDefinitions::ROOT_MENU_SLUG,
			__( 'Fundrik Settings', 'fundrik' ),
			__( 'Settings', 'fundrik' ),
			self::CAPABILITY,
			AdminPageDefinitions::ROOT_MENU_SLUG,
		);
	}

	/**
	 * Renders the Fundrik settings page.
	 *
	 * @since 1.0.0
	 */
	private function render(): void {

		$campaigns_url = admin_url(
			sprintf(
				'edit.php?post_type=%s',
				CampaignPostTypeConfig::ID,
			),
		);

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Settings', 'fundrik' ) . '</h1>';
		echo '<p>' . esc_html__( 'Fundrik plugin settings will appear here.', 'fundrik' ) . '</p>';
		printf(
			'<p><a class="button button-secondary" href="%1$s">%2$s</a></p>',
			esc_url( $campaigns_url ),
			esc_html__( 'Open Campaigns', 'fundrik' ),
		);
		echo '</div>';
	}
}
