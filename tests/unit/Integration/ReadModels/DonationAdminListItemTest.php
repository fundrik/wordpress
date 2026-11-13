<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\ReadModels;

use Fundrik\WordPress\Integration\ReadModels\DonationAdminListItem;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( DonationAdminListItem::class )]
final class DonationAdminListItemTest extends FundrikTestCase {

	#[Test]
	public function accessors_return_constructor_values(): void {

		$item = new DonationAdminListItem(
			id: '123e4567-e89b-42d3-a456-426614174000',
			campaign_title: 'Help the shelter',
			campaign_edit_url: 'https://example.test/wp-admin/post.php?post=42&action=edit',
			amount: '1,000.00 RUB',
			status: 'succeeded',
			created_at: '2026-11-01 10:00:00',
			updated_at: '2026-11-02 11:30:00',
		);

		self::assertSame( '123e4567-e89b-42d3-a456-426614174000', $item->get_id() );
		self::assertSame( 'Help the shelter', $item->get_campaign_title() );
		self::assertSame(
			'https://example.test/wp-admin/post.php?post=42&action=edit',
			$item->get_campaign_edit_url(),
		);
		self::assertSame( '1,000.00 RUB', $item->get_amount() );
		self::assertSame( 'succeeded', $item->get_status() );
		self::assertSame( '2026-11-01 10:00:00', $item->get_created_at() );
		self::assertSame( '2026-11-02 11:30:00', $item->get_updated_at() );
	}
}
