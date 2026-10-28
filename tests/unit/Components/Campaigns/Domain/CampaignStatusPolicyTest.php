<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Domain;

use Fundrik\WordPress\Components\Campaigns\Domain\CampaignStatusPolicy;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignStatusPolicy::class )]
final class CampaignStatusPolicyTest extends FundrikTestCase {

	#[Test]
	public function resolve_returns_donations_disabled_when_campaign_does_not_accept_donations(): void {

		$policy = new CampaignStatusPolicy();

		$status = $policy->resolve( false, 1_000, 1_000 );

		self::assertSame( 'donations_disabled', $status );
	}

	#[Test]
	public function resolve_returns_active_when_campaign_has_no_target(): void {

		$policy = new CampaignStatusPolicy();

		$status = $policy->resolve( true, null, 1_000 );

		self::assertSame( 'active', $status );
	}

	#[Test]
	public function resolve_returns_active_when_collected_amount_is_below_target(): void {

		$policy = new CampaignStatusPolicy();

		$status = $policy->resolve( true, 1_000, 999 );

		self::assertSame( 'active', $status );
	}

	#[Test]
	public function resolve_returns_target_reached_when_collected_amount_equals_target(): void {

		$policy = new CampaignStatusPolicy();

		$status = $policy->resolve( true, 1_000, 1_000 );

		self::assertSame( 'target_reached', $status );
	}

	#[Test]
	public function resolve_returns_target_reached_when_collected_amount_exceeds_target(): void {

		$policy = new CampaignStatusPolicy();

		$status = $policy->resolve( true, 1_000, 1_001 );

		self::assertSame( 'target_reached', $status );
	}
}
