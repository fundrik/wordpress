<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\Events\CampaignSavedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( CampaignSavedEvent::class )]
final class CampaignSavedEventTest extends TestCase {

	#[Test]
	public function it_exposes_id_and_is_update_flag_for_create(): void {

		$id = EntityId::create( 100 );
		$event = new CampaignSavedEvent( $id, false );

		$this->assertTrue( $event->campaign_id->equals( $id ) );
		$this->assertSame( 100, $event->campaign_id->to_int() );
		$this->assertFalse( $event->is_update );
	}

	#[Test]
	public function it_exposes_id_and_is_update_flag_for_update(): void {

		$id = EntityId::create( 100 );
		$event = new CampaignSavedEvent( $id, true );

		$this->assertTrue( $event->campaign_id->equals( $id ) );
		$this->assertSame( 100, $event->campaign_id->to_int() );
		$this->assertTrue( $event->is_update );
	}

	#[Test]
	public function it_accepts_uuid_ids(): void {

		$uuid = '2b7a1b8a-6f0e-4a87-a3b1-6f3d4c9a2e10';
		$id = EntityId::create( $uuid );
		$event = new CampaignSavedEvent( $id, true );

		$this->assertTrue( $event->campaign_id->equals( $id ) );
		$this->assertSame( $uuid, $event->campaign_id->to_uuid() );
		$this->assertTrue( $event->is_update );
	}
}
