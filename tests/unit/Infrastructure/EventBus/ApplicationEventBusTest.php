<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventBus;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBus;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBusException;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventPublisherPort;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

#[CoversClass( ApplicationEventBus::class )]
#[CoversClass( ApplicationEventBusException::class )]
final class ApplicationEventBusTest extends MockeryTestCase {

	private ApplicationEventPublisherPort&MockInterface $publisher;
	private ApplicationEventBus $event_bus;

	protected function setUp(): void {

		parent::setUp();

		$this->publisher = Mockery::mock( ApplicationEventPublisherPort::class );
		$this->event_bus = new ApplicationEventBus( $this->publisher );
	}

	#[Test]
	public function publish_delegates_event_to_configured_publisher(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 10 ) );

		$this->publisher
			->shouldReceive( 'publish' )
			->once()
			->with( $event );

		$this->event_bus->publish( $event );
	}

	#[Test]
	public function publish_wraps_publisher_errors_into_event_bus_exception(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 10 ) );
		$previous = new RuntimeException( 'Publisher failed.' );

		$this->publisher
			->shouldReceive( 'publish' )
			->once()
			->with( $event )
			->andThrow( $previous );

		$this->expectException( ApplicationEventBusException::class );
		$this->expectExceptionMessage( 'Cannot publish application event.' );

		try {
			$this->event_bus->publish( $event );
		} catch ( ApplicationEventBusException $e ) {
			self::assertSame( $previous, $e->getPrevious() );
			throw $e;
		}
	}
}
