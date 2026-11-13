<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\ReadModels;

use Fundrik\WordPress\Integration\ReadModels\DonationAdminListItem;
use Fundrik\WordPress\Integration\ReadModels\PaginatedDonationsAdminList;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( PaginatedDonationsAdminList::class )]
#[UsesClass( DonationAdminListItem::class )]
final class PaginatedDonationsAdminListTest extends FundrikTestCase {

	#[Test]
	public function accessors_return_constructor_values(): void {

		$item = new DonationAdminListItem(
			id: '123e4567-e89b-42d3-a456-426614174000',
			campaign_title: 'Help the shelter',
			campaign_edit_url: 'https://example.test/wp-admin/post.php?post=42&action=edit',
			amount: '1,000.00 RUB',
			status: 'succeeded',
			created_at: '2026-11-01 10:00:00',
			updated_at: null,
		);

		$result = new PaginatedDonationsAdminList(
			items: [ $item ],
			page: 2,
			per_page: 20,
			total: 41,
		);

		self::assertSame( [ $item ], $result->get_items() );
		self::assertSame( 2, $result->get_page() );
		self::assertSame( 20, $result->get_per_page() );
		self::assertSame( 41, $result->get_total() );
	}

	#[Test]
	public function get_total_pages_rounds_partial_page_up(): void {

		$result = new PaginatedDonationsAdminList( [], 2, 20, 41 );

		self::assertSame( 3, $result->get_total_pages() );
	}

	#[Test]
	public function get_total_pages_returns_zero_when_list_is_empty(): void {

		$result = new PaginatedDonationsAdminList( [], 1, 20, 0 );

		self::assertSame( 0, $result->get_total_pages() );
	}
}
