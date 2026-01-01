<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\Integration\Events\ActionPostDeletedEvent;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WP_Post;

#[CoversClass( ActionPostDeletedEvent::class )]
final class ActionPostDeletedEventTest extends MockeryTestCase {

	private WP_Post&MockInterface $post;
	private WordPressContextInterface&MockInterface $context;

	protected function setUp(): void {

		parent::setUp();

		$this->post = Mockery::mock( WP_Post::class );
		$this->context = Mockery::mock( WordPressContextInterface::class );
	}

	#[Test]
	public function it_accepts_post_id_post_and_context(): void {

		$event = new ActionPostDeletedEvent( 42, $this->post, $this->context );

		$this->assertSame( 42, $event->post_id );
		$this->assertSame( $this->post, $event->post );
		$this->assertSame( $this->context, $event->context );
	}
}
