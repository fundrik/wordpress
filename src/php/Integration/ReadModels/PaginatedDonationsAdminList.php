<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Integration\ReadModels;

/**
 * Represents a paginated list of donation rows for the admin donations list.
 *
 * @since 1.0.0
 *
 * @internal
 */
final readonly class PaginatedDonationsAdminList {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, DonationAdminListItem> $items Donation rows.
	 * @param int $page Page number.
	 * @param int $per_page Rows per page.
	 * @param int $total Total number of rows.
	 *
	 * @phpstan-param list<DonationAdminListItem> $items
	 */
	public function __construct(
		private array $items,
		private int $page,
		private int $per_page,
		private int $total,
	) {}

	/**
	 * Returns the donation rows.
	 *
	 * @since 1.0.0
	 *
	 * @return list<DonationAdminListItem> Donation rows.
	 */
	public function get_items(): array {

		return $this->items;
	}

	/**
	 * Returns the page number.
	 *
	 * @since 1.0.0
	 *
	 * @return int Page number.
	 */
	public function get_page(): int {

		return $this->page;
	}

	/**
	 * Returns the rows per page.
	 *
	 * @since 1.0.0
	 *
	 * @return int Rows per page.
	 */
	public function get_per_page(): int {

		return $this->per_page;
	}

	/**
	 * Returns the total number of rows.
	 *
	 * @since 1.0.0
	 *
	 * @return int Total number of rows.
	 */
	public function get_total(): int {

		return $this->total;
	}

	/**
	 * Returns the total number of pages.
	 *
	 * @since 1.0.0
	 *
	 * @return int Total number of pages.
	 */
	public function get_total_pages(): int {

		if ( $this->total === 0 ) {
			return 0;
		}

		return (int) ceil( $this->total / $this->per_page );
	}
}
