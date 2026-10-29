<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\EventBus;

use Fundrik\Core\Components\Donations\Application\Events\DonationSucceededEvent;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventBus;
use Fundrik\WordPress\Infrastructure\EventBus\ApplicationEventConsumerInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

#[CoversClass( ApplicationEventBus::class )]
final class ApplicationEventBusTest extends MockeryTestCase {

	private const string DONATION_ID = '019b6bcb-2f32-4461-838f-67a1479fbdbe';

	private ApplicationEventConsumerInterface&MockInterface $first_consumer;
	private ApplicationEventConsumerInterface&MockInterface $second_consumer;

	private ApplicationEventBus $bus;
	private DonationSucceededEvent $event;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->event = new DonationSucceededEvent( EntityId::create( self::DONATION_ID ) );
		$this->first_consumer = Mockery::mock( ApplicationEventConsumerInterface::class );
		$this->second_consumer = Mockery::mock( ApplicationEventConsumerInterface::class );
		$this->bus = new ApplicationEventBus( $this->first_consumer, $this->second_consumer );
	}

	#[Test]
	public function publish_dispatches_event_to_each_consumer(): void {

		$this->first_consumer
			->shouldReceive( 'consume' )
			->once()
			->with( $this->event );

		$this->second_consumer
			->shouldReceive( 'consume' )
			->once()
			->with( $this->event );

		$this->bus->publish( $this->event );
	}

	#[Test]
	public function publish_continues_dispatch_when_consumer_throws(): void {

		$this->first_consumer
			->shouldReceive( 'consume' )
			->once()
			->with( $this->event )
			->andThrow( new RuntimeException( 'Consumer failed.' ) );

		$this->second_consumer
			->shouldReceive( 'consume' )
			->once()
			->with( $this->event );

		$this->bus->publish( $this->event );
	}
}
