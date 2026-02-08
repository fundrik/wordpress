<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Events;

use Fundrik\WordPress\Integration\Events\ActionRegisterPostTypesEvent;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( ActionRegisterPostTypesEvent::class )]
final class ActionRegisterPostTypesEventTest extends MockeryTestCase {

	private WordPressContextInterface&MockInterface $context;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );
	}

	#[Test]
	public function it_exposes_the_context(): void {

		$event = new ActionRegisterPostTypesEvent( $this->context );

		$this->assertSame( $this->context, $event->context );
	}
}
