<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration;

use Brain\Monkey\Actions;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Integration\WordPressActionApplicationEventPublisher;
use Fundrik\WordPress\Tests\Fixtures\DummyApplicationEvent;
use Fundrik\WordPress\Tests\WordPressTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( WordPressActionApplicationEventPublisher::class )]
final class WordPressActionApplicationEventPublisherTest extends WordPressTestCase {

	#[Test]
	public function publish_dispatches_campaign_created_event_to_wordpress_actions(): void {

		$publisher = new WordPressActionApplicationEventPublisher();
		$event = new CampaignCreatedEvent( EntityId::create( 11 ) );

		Actions\expectDone( 'fundrik_campaign_created' )
			->once()
			->with( 11 );

		Actions\expectDone( 'fundrik_campaign_saved' )
			->once()
			->with( 11 );

		$publisher->publish( $event );
	}

	#[Test]
	public function publish_dispatches_campaign_updated_event_to_wordpress_actions(): void {

		$publisher = new WordPressActionApplicationEventPublisher();
		$event = new CampaignUpdatedEvent( EntityId::create( 12 ) );

		Actions\expectDone( 'fundrik_campaign_updated' )
			->once()
			->with( 12 );

		Actions\expectDone( 'fundrik_campaign_saved' )
			->once()
			->with( 12 );

		$publisher->publish( $event );
	}

	#[Test]
	public function publish_dispatches_campaign_deleted_event_to_wordpress_actions(): void {

		$publisher = new WordPressActionApplicationEventPublisher();
		$event = new CampaignDeletedEvent( EntityId::create( 13 ) );

		Actions\expectDone( 'fundrik_campaign_deleted' )
			->once()
			->with( 13 );

		$publisher->publish( $event );
	}

	#[Test]
	public function publish_ignores_unknown_events(): void {

		$publisher = new WordPressActionApplicationEventPublisher();
		$event = new DummyApplicationEvent();

		Actions\expectDone( 'fundrik_campaign_created' )->never();
		Actions\expectDone( 'fundrik_campaign_updated' )->never();
		Actions\expectDone( 'fundrik_campaign_deleted' )->never();
		Actions\expectDone( 'fundrik_campaign_saved' )->never();

		$publisher->publish( $event );
	}
}
