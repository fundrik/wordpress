<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Integration\Events;

use Fundrik\WordPress\Infrastructure\Integration\Events\FilterRestPrepareCampaignEvent;
use Fundrik\WordPress\Infrastructure\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

#[CoversClass( FilterRestPrepareCampaignEvent::class )]
final class FilterRestPrepareCampaignEventTest extends MockeryTestCase {

	private WP_REST_Response&MockInterface $response;
	private WP_Post&MockInterface $post;
	private WP_REST_Request&MockInterface $request;
	private WordPressContextInterface&MockInterface $context;

	protected function setUp(): void {

		parent::setUp();

		$this->response = Mockery::mock( WP_REST_Response::class );
		$this->post = Mockery::mock( WP_Post::class );
		$this->request = Mockery::mock( WP_REST_Request::class );
		$this->context = Mockery::mock( WordPressContextInterface::class );
	}

	#[Test]
	public function it_accepts_response_post_request_and_context(): void {

		$event = new FilterRestPrepareCampaignEvent( $this->response, $this->post, $this->request, $this->context );

		$this->assertSame( $this->response, $event->response );
		$this->assertSame( $this->post, $event->post );
		$this->assertSame( $this->request, $event->request );
		$this->assertSame( $this->context, $event->context );
	}

	#[Test]
	public function it_allows_modification_of_response(): void {

		$event = new FilterRestPrepareCampaignEvent( $this->response, $this->post, $this->request, $this->context );

		$new_response = Mockery::mock( WP_REST_Response::class );

		$event->response = $new_response;

		$this->assertSame( $new_response, $event->response );
		$this->assertSame( $this->post, $event->post );
		$this->assertSame( $this->request, $event->request );
		$this->assertSame( $this->context, $event->context );
	}
}
