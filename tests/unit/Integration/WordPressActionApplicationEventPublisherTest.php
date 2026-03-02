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
use Fundrik\WordPress\Tests\Fixtures\DummyCampaignApplicationEvent;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( WordPressActionApplicationEventPublisher::class )]
final class WordPressActionApplicationEventPublisherTest extends WordPressTestCase {

	private LoggerInterface&MockInterface $logger;
	private WordPressActionApplicationEventPublisher $publisher;

	protected function setUp(): void {

		parent::setUp();

		$this->logger = Mockery::mock( LoggerInterface::class );
		$this->publisher = new WordPressActionApplicationEventPublisher( $this->logger );
	}

	#[Test]
	public function publish_dispatches_campaign_created_event_to_wordpress_actions(): void {

		$event = new CampaignCreatedEvent( EntityId::create( 11 ) );

		Actions\expectDone( 'fundrik_campaign_created' )
			->once()
			->with( 11 );

		$this->publisher->publish( $event );
	}

	#[Test]
	public function publish_dispatches_campaign_updated_event_to_wordpress_actions(): void {

		$event = new CampaignUpdatedEvent( EntityId::create( 12 ) );

		Actions\expectDone( 'fundrik_campaign_updated' )
			->once()
			->with( 12 );

		$this->publisher->publish( $event );
	}

	#[Test]
	public function publish_dispatches_campaign_deleted_event_to_wordpress_actions(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 13 ) );

		Actions\expectDone( 'fundrik_campaign_deleted' )
			->once()
			->with( 13 );

		$this->publisher->publish( $event );
	}

	#[Test]
	public function publish_logs_warning_when_campaign_id_cannot_be_resolved(): void {

		$event = new CampaignCreatedEvent(
			EntityId::create( '2be078f7-7d75-4450-90d0-e7fd204f072e' ),
		);

		$this->logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Publishing application event skipped due to invalid campaign ID.',
				Mockery::subset(
					[
						'service_class' => WordPressActionApplicationEventPublisher::class,
						'logger_class' => WordPressActionApplicationEventPublisher::class,
						'component' => 'event_bus',
						'layer' => 'integration',
						'system' => 'wordpress',
						'operation' => 'publish',
						'outcome' => 'invalid',
						'event_class' => CampaignCreatedEvent::class,
						'reason' => 'campaign_id_not_int',
					],
				),
			);

		Actions\expectDone( 'fundrik_campaign_created' )->never();
		Actions\expectDone( 'fundrik_campaign_updated' )->never();
		Actions\expectDone( 'fundrik_campaign_deleted' )->never();

		$this->publisher->publish( $event );
	}

	#[Test]
	public function publish_ignores_non_campaign_event(): void {

		$event = new DummyApplicationEvent();

		Actions\expectDone( 'fundrik_campaign_created' )->never();
		Actions\expectDone( 'fundrik_campaign_updated' )->never();
		Actions\expectDone( 'fundrik_campaign_deleted' )->never();

		$this->publisher->publish( $event );
	}

	#[Test]
	public function publish_ignores_unsupported_campaign_event(): void {

		$event = new DummyCampaignApplicationEvent( EntityId::create( 17 ) );

		Actions\expectDone( 'fundrik_campaign_created' )->never();
		Actions\expectDone( 'fundrik_campaign_updated' )->never();
		Actions\expectDone( 'fundrik_campaign_deleted' )->never();

		$this->publisher->publish( $event );
	}
}
