<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\Integration\Events\PostSavedEvent;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WP_Post;

#[CoversClass( PostSavedEvent::class )]
final class PostSavedEventTest extends MockeryTestCase {

	private WP_Post&MockInterface $post;
	private WP_Post&MockInterface $post_before;
	private WordPressContextInterface&MockInterface $context;

	protected function setUp(): void {

		parent::setUp();

		$this->post = Mockery::mock( WP_Post::class );
		$this->post_before = Mockery::mock( WP_Post::class );
		$this->context = Mockery::mock( WordPressContextInterface::class );
	}

	#[Test]
	public function it_exposes_all_fields_for_updated_post(): void {

		$event = new PostSavedEvent(
			post_id: 456,
			post: $this->post,
			update: true,
			post_before: $this->post_before,
			context: $this->context,
		);

		$this->assertSame( 456, $event->post_id );
		$this->assertSame( $this->post, $event->post );
		$this->assertTrue( $event->update );
		$this->assertSame( $this->post_before, $event->post_before );
		$this->assertSame( $this->context, $event->context );
	}

	#[Test]
	public function it_allows_null_post_before_for_new_post(): void {

		$event = new PostSavedEvent(
			post_id: 789,
			post: $this->post,
			update: false,
			post_before: null,
			context: $this->context,
		);

		$this->assertSame( 789, $event->post_id );
		$this->assertSame( $this->post, $event->post );
		$this->assertFalse( $event->update );
		$this->assertNull( $event->post_before );
		$this->assertSame( $this->context, $event->context );
	}
}
