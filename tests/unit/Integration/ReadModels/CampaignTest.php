<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\ReadModels;

use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( Campaign::class )]
final class CampaignTest extends FundrikTestCase {

	#[Test]
	public function accessors_return_constructor_values(): void {

		$created_at = UtcDateTime::create_from_format( '2026-11-01 10:00:00', 'Y-m-d H:i:s' );
		$updated_at = UtcDateTime::create_from_format( '2026-11-02 11:30:00', 'Y-m-d H:i:s' );

		$campaign = new Campaign(
			id: 42,
			title: 'Help the shelter',
			permalink: 'https://example.test/campaigns/help-the-shelter/',
			featured_image_id: 91,
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 100_000,
			collected_amount: 25_000,
			donations_count: 17,
			created_at: $created_at,
			updated_at: $updated_at,
		);

		self::assertSame( 42, $campaign->get_id() );
		self::assertSame( 'Help the shelter', $campaign->get_title() );
		self::assertTrue( $campaign->accepts_donations() );
		self::assertSame( 'RUB', $campaign->get_currency_code() );
		self::assertTrue( $campaign->has_target() );
		self::assertSame( 100_000, $campaign->get_target_amount() );
		self::assertSame( 25_000, $campaign->get_collected_amount() );
		self::assertSame( 17, $campaign->get_donations_count() );
		self::assertSame( $created_at, $campaign->get_created_at() );
		self::assertSame( $updated_at, $campaign->get_updated_at() );
		self::assertSame( 'https://example.test/campaigns/help-the-shelter/', $campaign->get_permalink() );
		self::assertSame( 91, $campaign->get_featured_image_id() );
	}

	#[Test]
	public function has_target_returns_false_when_target_is_not_configured(): void {

		$campaign = new Campaign(
			id: 42,
			title: 'Help the shelter',
			permalink: null,
			featured_image_id: null,
			accepts_donations: false,
			currency_code: 'RUB',
			target_amount: null,
			collected_amount: 0,
			donations_count: 0,
			created_at: UtcDateTime::create_from_format( '2026-11-01 10:00:00', 'Y-m-d H:i:s' ),
			updated_at: null,
		);

		self::assertFalse( $campaign->has_target() );
	}
}
