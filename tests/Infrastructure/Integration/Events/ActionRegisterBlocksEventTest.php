<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\Integration\Events\ActionRegisterBlocksEvent;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( ActionRegisterBlocksEvent::class )]
final class ActionRegisterBlocksEventTest extends MockeryTestCase {

	private WordPressContextInterface&MockInterface $context;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
	}

	#[Test]
	public function it_exposes_the_context(): void {

		$event = new ActionRegisterBlocksEvent( $this->context );

		$this->assertSame( $this->context, $event->context );
	}
}
