<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Domain;

use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignSlug;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignException;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( Campaign::class )]
#[UsesClass( CampaignSlug::class )]
final class CampaignTest extends FundrikTestCase {

	#[Test]
	public function campaign_returns_all_expected_values(): void {

		$campaign = $this->make_campaign();

		$this->assertSame( 1, $campaign->get_id() );
		$this->assertSame( 'Test Campaign', $campaign->get_title() );
		$this->assertSame( 'test-campaign', $campaign->get_slug() );
		$this->assertTrue( $campaign->is_active() );
		$this->assertTrue( $campaign->is_open() );
		$this->assertTrue( $campaign->has_target() );
		$this->assertSame( 100, $campaign->get_target_amount() );
	}

	#[Test]
	public function it_throws_when_id_is_not_castable_to_int(): void {

		$campaign = $this->make_campaign( id: '7f2c8a19-8b3a-42e0-8573-5e672c7e4f01' );

		$this->expectException( InvalidCampaignException::class );
		$this->expectExceptionMessage( 'Expected int-compatible ID in Campaign, got invalid value:' );

		$campaign->get_id();
	}
}
