<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages\Pages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageDefinitions;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\Tables\DonationsListTable;
use Fundrik\WordPress\Integration\Services\DonationsListService;
use Override;

/**
 * Represents the Fundrik donations admin page.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class DonationsAdminPage implements AdminPageInterface {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationsListService $donations_list_service Provides paginated donation rows.
	 */
	public function __construct(
		private DonationsListService $donations_list_service,
	) {}

	/**
	 * Registers the Fundrik donations submenu page.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function register(): void {

		add_submenu_page(
			AdminPageDefinitions::ROOT_MENU_SLUG,
			__( 'Fundrik Donations', 'fundrik' ),
			__( 'Donations', 'fundrik' ),
			AdminPageDefinitions::CONTENT_CAPABILITY,
			AdminPageDefinitions::DONATIONS_PAGE_ID,
			$this->render( ... ),
		);
	}

	/**
	 * Renders the donations list page.
	 *
	 * @since 1.0.0
	 */
	private function render(): void {

		$table = new DonationsListTable( $this->donations_list_service );
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fundrik Donations', 'fundrik' ); ?></h1>
			<form method="get">
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
}
