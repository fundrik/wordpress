<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventBus;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBus;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventConsumerInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

#[CoversClass( ApplicationEventBus::class )]
final class ApplicationEventBusTest extends MockeryTestCase {

	private ApplicationEventConsumerInterface&MockInterface $first_consumer;
	private ApplicationEventConsumerInterface&MockInterface $second_consumer;
	private ApplicationEventBus $event_bus;

	protected function setUp(): void {

		parent::setUp();

		$this->first_consumer = Mockery::mock( ApplicationEventConsumerInterface::class );
		$this->second_consumer = Mockery::mock( ApplicationEventConsumerInterface::class );
		$this->event_bus = new ApplicationEventBus( $this->first_consumer, $this->second_consumer );
	}

	#[Test]
	public function publish_delegates_event_to_all_configured_consumers_in_order(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 10 ) );

		$this->first_consumer
			->shouldReceive( 'consume' )
			->once()
			->ordered()
			->with( $event );

		$this->second_consumer
			->shouldReceive( 'consume' )
			->once()
			->ordered()
			->with( $event );

		$this->event_bus->publish( $event );
	}

	#[Test]
	public function publish_skips_failed_consumers_and_continues_dispatch(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 10 ) );

		$this->first_consumer
			->shouldReceive( 'consume' )
			->once()
			->with( $event )
			->andThrow( new RuntimeException( 'Consumer failed.' ) );

		$this->second_consumer
			->shouldReceive( 'consume' )
			->once()
			->with( $event );

		$this->event_bus->publish( $event );
	}
}
