<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventBus;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBus;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBusException;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventListenerInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

#[CoversClass( ApplicationEventBus::class )]
#[CoversClass( ApplicationEventBusException::class )]
final class ApplicationEventBusTest extends MockeryTestCase {

	private ApplicationEventListenerInterface&MockInterface $first_listener;
	private ApplicationEventListenerInterface&MockInterface $second_listener;
	private ApplicationEventBus $event_bus;

	protected function setUp(): void {

		parent::setUp();

		$this->first_listener = Mockery::mock( ApplicationEventListenerInterface::class );
		$this->second_listener = Mockery::mock( ApplicationEventListenerInterface::class );
		$this->event_bus = new ApplicationEventBus( $this->first_listener, $this->second_listener );
	}

	#[Test]
	public function publish_delegates_event_to_all_configured_listeners_in_order(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 10 ) );

		$this->first_listener
			->shouldReceive( 'handle' )
			->once()
			->ordered()
			->with( $event );

		$this->second_listener
			->shouldReceive( 'handle' )
			->once()
			->ordered()
			->with( $event );

		$this->event_bus->publish( $event );
	}

	#[Test]
	public function publish_wraps_listener_errors_into_event_bus_exception(): void {

		$event = new CampaignDeletedEvent( EntityId::create( 10 ) );
		$previous = new RuntimeException( 'Listener failed.' );

		$this->first_listener
			->shouldReceive( 'handle' )
			->once()
			->with( $event )
			->andThrow( $previous );

		$this->second_listener
			->shouldNotReceive( 'handle' );

		$this->expectException( ApplicationEventBusException::class );
		$this->expectExceptionMessage( 'Failed to dispatch application event' );

		try {
			$this->event_bus->publish( $event );
		} catch ( ApplicationEventBusException $e ) {
			self::assertSame( $previous, $e->getPrevious() );
			throw $e;
		}
	}
}
