<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Events;

use Fundrik\WordPress\Integration\Events\FilterCampaignBeforeSavedViaRestEvent;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use WP_Error;
use WP_REST_Request;

#[CoversClass( FilterCampaignBeforeSavedViaRestEvent::class )]
final class FilterCampaignBeforeSavedViaRestEventTest extends MockeryTestCase {

	private WP_REST_Request&MockInterface $request;
	private WordPressContextInterface&MockInterface $context;

	protected function setUp(): void {

		parent::setUp();

		$this->request = Mockery::mock( WP_REST_Request::class );
		$this->context = Mockery::mock( WordPressContextInterface::class );
	}

	#[Test]
	public function it_accepts_prepared_post_request_and_context(): void {

		$prepared_post = new stdClass();
		$prepared_post->post_title = 'Test Campaign';

		$event = new FilterCampaignBeforeSavedViaRestEvent( $prepared_post, $this->request, $this->context );

		$this->assertSame( $prepared_post, $event->prepared_post );
		$this->assertSame( 'Test Campaign', $event->prepared_post->post_title );
		$this->assertSame( $this->request, $event->request );
		$this->assertSame( $this->context, $event->context );
	}

	#[Test]
	public function it_allows_modification_of_prepared_post(): void {

		$prepared_post = new stdClass();

		$event = new FilterCampaignBeforeSavedViaRestEvent( $prepared_post, $this->request, $this->context );

		$event->prepared_post->post_content = 'Modified Content';

		$this->assertSame( 'Modified Content', $event->prepared_post->post_content );
	}

	#[Test]
	public function it_is_not_rejected_by_default(): void {

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			new stdClass(),
			$this->request,
			$this->context,
		);

		$this->assertFalse( $event->is_rejected() );
		$this->assertNull( $event->get_rejection_error() );
	}

	#[Test]
	public function it_can_be_rejected_with_error(): void {

		$event = new FilterCampaignBeforeSavedViaRestEvent(
			new stdClass(),
			$this->request,
			$this->context,
		);

		$error = new WP_Error( 'fundrik_rejected', 'Rejected.' );

		$event->reject( $error );

		$this->assertTrue( $event->is_rejected() );
		$this->assertSame( $error, $event->get_rejection_error() );
	}
}
