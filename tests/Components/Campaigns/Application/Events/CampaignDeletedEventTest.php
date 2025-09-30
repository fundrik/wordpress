<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass( CampaignDeletedEvent::class )]
final class CampaignDeletedEventTest extends TestCase {

	#[Test]
	public function it_exposes_entity_id_for_numeric_id(): void {

		$id = EntityId::create( 42 );
		$event = new CampaignDeletedEvent( $id );

		$this->assertTrue( $event->campaign_id->equals( $id ) );
		$this->assertSame( 42, $event->campaign_id->to_int() );
	}

	#[Test]
	public function it_accepts_uuid_ids(): void {

		$uuid = '7c1bb0b8-4d8e-4b3a-9a6e-3f1d9b1b6f5b';
		$id = EntityId::create( $uuid );
		$event = new CampaignDeletedEvent( $id );

		$this->assertTrue( $event->campaign_id->equals( $id ) );
		$this->assertSame( $uuid, $event->campaign_id->to_uuid() );
	}
}
