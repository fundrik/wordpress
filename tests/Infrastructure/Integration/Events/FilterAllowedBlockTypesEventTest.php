<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WP_Block_Editor_Context;

#[CoversClass( FilterAllowedBlockTypesEvent::class )]
final class FilterAllowedBlockTypesEventTest extends MockeryTestCase {

	private WP_Block_Editor_Context&MockInterface $editor_context;
	private WordPressContextInterface&MockInterface $context;

	protected function setUp(): void {

		parent::setUp();

		$this->editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$this->context = Mockery::mock( WordPressContextInterface::class );
	}

	#[Test]
	public function it_accepts_true_as_allowed(): void {

		$event = new FilterAllowedBlockTypesEvent( true, $this->editor_context, $this->context );

		$this->assertTrue( $event->allowed );
		$this->assertSame( $this->editor_context, $event->editor_context );
		$this->assertSame( $this->context, $event->context );
	}

	#[Test]
	public function it_accepts_false_as_allowed(): void {

		$event = new FilterAllowedBlockTypesEvent( false, $this->editor_context, $this->context );

		$this->assertFalse( $event->allowed );
		$this->assertSame( $this->editor_context, $event->editor_context );
		$this->assertSame( $this->context, $event->context );
	}

	#[Test]
	public function it_accepts_array_as_allowed(): void {

		$blocks = [ 'core/paragraph', 'core/image' ];

		$event = new FilterAllowedBlockTypesEvent( $blocks, $this->editor_context, $this->context );

		$this->assertSame( $blocks, $event->allowed );
		$this->assertSame( $this->editor_context, $event->editor_context );
		$this->assertSame( $this->context, $event->context );
	}

	#[Test]
	public function it_allows_modification_of_allowed_field(): void {

		$event = new FilterAllowedBlockTypesEvent( true, $this->editor_context, $this->context );

		$event->allowed = [ 'core/quote' ];

		$this->assertSame( [ 'core/quote' ], $event->allowed );
	}
}
