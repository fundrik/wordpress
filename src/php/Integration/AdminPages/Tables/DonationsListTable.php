<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\AdminPages\Tables;

use Fundrik\WordPress\Integration\ReadModels\DonationAdminListItem;
use Fundrik\WordPress\Integration\Services\DonationsListService;
use Override;
use WP_List_Table;

/**
 * Provides the donations list table for the admin page.
 *
 * @since 1.0.0
 *
 * @internal
 */
final class DonationsListTable extends WP_List_Table {

	private const int PER_PAGE = 20;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationsListService $donations_list_service Provides paginated donation rows.
	 */
	public function __construct(
		private readonly DonationsListService $donations_list_service,
	) {

		parent::__construct(
			[
				'singular' => 'donation',
				'plural' => 'donations',
			],
		);
	}

	/**
	 * Prepares the list table items for display.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function prepare_items(): void {

		$page = $this->get_pagenum();
		$paginated_donations = $this->donations_list_service->paginate( $page, self::PER_PAGE );

		$this->items = $paginated_donations->get_items();

		// TODO: Decide if it is really necessary.
		$this->_column_headers = [
			$this->get_columns(),
			[],
			[],
		];

		$this->set_pagination_args(
			[
				'total_items' => $paginated_donations->get_total(),
				'per_page' => self::PER_PAGE,
			],
		);
	}

	/**
	 * Returns the list table columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Table columns.
	 */
	#[Override]
	public function get_columns(): array {

		return [
			'id' => __( 'ID', 'fundrik' ),
			'campaign' => __( 'Campaign', 'fundrik' ),
			'amount' => __( 'Amount', 'fundrik' ),
			'status' => __( 'Status', 'fundrik' ),
			'created_at' => __( 'Created at', 'fundrik' ),
			'updated_at' => __( 'Updated at', 'fundrik' ),
		];
	}

	/**
	 * Returns the default column output.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationAdminListItem $item Donation row.
	 * @param string $column_name Column name.
	 *
	 * @return string Column output.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	#[Override]
	public function column_default( $item, $column_name ): string {

		if ( ! $item instanceof DonationAdminListItem ) {
			return '';
		}

		return match ( $column_name ) {
			'id' => esc_html( $item->get_id() ),
			'campaign' => $this->render_campaign_cell( $item->get_campaign_title(), $item->get_campaign_edit_url() ),
			'amount' => esc_html( $item->get_amount() ),
			'status' => esc_html( $item->get_status() ),
			'created_at' => esc_html( $item->get_created_at() ),
			'updated_at' => esc_html( $item->get_updated_at() ?? '-' ),
			default => '',
		};
	}

	/**
	 * Renders the no items message.
	 *
	 * @since 1.0.0
	 */
	#[Override]
	public function no_items(): void {

		esc_html_e( 'No donations found.', 'fundrik' );
	}

	/**
	 * Returns the campaign cell markup.
	 *
	 * @since 1.0.0
	 *
	 * @param string $campaign_title Campaign title.
	 * @param string $campaign_edit_url Campaign edit URL.
	 *
	 * @return string Campaign cell markup.
	 */
	private function render_campaign_cell( string $campaign_title, string $campaign_edit_url ): string {

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( $campaign_edit_url ),
			esc_html( $campaign_title ),
		);
	}
}
