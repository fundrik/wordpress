<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Listeners;

use Fundrik\WordPress\Integration\Events\FilterAllowedBlockTypesEvent;
use Fundrik\WordPress\Integration\Listeners\FilterAllowedBlocksByPostTypeListener;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeId;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeIdReader;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlock;
use Fundrik\WordPress\Integration\PostTypes\Attributes\PostTypeSpecificBlockReader;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostType;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\BetaPostType;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use WP_Block_Editor_Context;
use WP_Block_Type;
use WP_Post;

#[CoversClass( FilterAllowedBlocksByPostTypeListener::class )]
#[UsesClass( FilterAllowedBlockTypesEvent::class )]
#[UsesClass( PostTypeId::class )]
#[UsesClass( PostTypeIdReader::class )]
#[UsesClass( PostTypeSpecificBlock::class )]
#[UsesClass( PostTypeSpecificBlockReader::class )]
final class FilterAllowedBlocksByPostTypeListenerTest extends MockeryTestCase {

	private WordPressContextInterface&MockInterface $context;

	private FilterAllowedBlocksByPostTypeListener $listener;

	protected function setUp(): void {

		parent::setUp();

		$this->context = Mockery::mock( WordPressContextInterface::class );

		$this->listener = new FilterAllowedBlocksByPostTypeListener(
			new PostTypeIdReader(),
			new PostTypeSpecificBlockReader(),
		);
	}

	#[Test]
	public function handle_filters_restricted_blocks_for_current_post_type_and_keeps_unrestricted(): void {

		$editor_context = $this->make_editor_context_with_post_type( 'alpha' );

		$this->context
			->shouldReceive( 'get_declared_post_type_classes' )
			->once()
			->andReturn( [ AlphaPostType::class, BetaPostType::class ] );

		// Not needed (allowed is an explicit array, not true).
		$this->context
			->shouldNotReceive( 'get_registered_block_types' );

		$event = new FilterAllowedBlockTypesEvent(
			allowed: [ 'core/paragraph', 'fundrik/alpha-only', 'fundrik/beta-only', 'fundrik/shared' ],
			editor_context: $editor_context,
			context: $this->context,
		);

		$this->listener->handle( $event );

		// Explanation:
		// - core/paragraph: unrestricted -> allowed
		// - fundrik/alpha-only: restricted to alpha -> allowed
		// - fundrik/beta-only: restricted to beta -> removed
		// - fundrik/shared: restricted to both alpha and beta -> allowed
		self::assertSame(
			[ 'core/paragraph', 'fundrik/alpha-only', 'fundrik/shared' ],
			$event->allowed,
		);
	}

	#[Test]
	public function handle_filters_restricted_blocks_out_for_other_post_type(): void {

		$editor_context = $this->make_editor_context_with_post_type( 'beta' );

		$this->context
			->shouldReceive( 'get_declared_post_type_classes' )
			->once()
			->andReturn( [ AlphaPostType::class, BetaPostType::class ] );

		$event = new FilterAllowedBlockTypesEvent(
			allowed: [ 'fundrik/alpha-only', 'fundrik/shared' ],
			editor_context: $editor_context,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertSame(
			[ 'fundrik/shared' ],
			$event->allowed,
		);
	}

	#[Test]
	public function handle_expands_allowed_true_to_registered_blocks_then_filters(): void {

		$editor_context = $this->make_editor_context_with_post_type( 'alpha' );

		$this->context
			->shouldReceive( 'get_registered_block_types' )
			->once()
			->andReturn(
				[
					'core/paragraph' => Mockery::mock( WP_Block_Type::class ),
					'fundrik/alpha-only' => Mockery::mock( WP_Block_Type::class ),
					'fundrik/beta-only' => Mockery::mock( WP_Block_Type::class ),
					'fundrik/shared' => Mockery::mock( WP_Block_Type::class ),
				],
			);

		$this->context
			->shouldReceive( 'get_declared_post_type_classes' )
			->once()
			->andReturn( [ AlphaPostType::class, BetaPostType::class ] );

		$event = new FilterAllowedBlockTypesEvent(
			allowed: true,
			editor_context: $editor_context,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertSame(
			[ 'core/paragraph', 'fundrik/alpha-only', 'fundrik/shared' ],
			$event->allowed,
		);
	}

	#[Test]
	public function handle_returns_early_when_allowed_is_false(): void {

		$editor_context = $this->make_editor_context_with_post_type( 'alpha' );

		$this->context
			->shouldNotReceive( 'get_registered_block_types' );

		$this->context
			->shouldNotReceive( 'get_declared_post_type_classes' );

		$event = new FilterAllowedBlockTypesEvent(
			allowed: false,
			editor_context: $editor_context,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertFalse( $event->allowed );
	}

	#[Test]
	public function handle_returns_early_when_editor_context_post_type_is_missing(): void {

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = null;

		$this->context
			->shouldNotReceive( 'get_registered_block_types' );

		$this->context
			->shouldNotReceive( 'get_declared_post_type_classes' );

		$allowed = [ 'core/paragraph' ];

		$event = new FilterAllowedBlockTypesEvent(
			allowed: $allowed,
			editor_context: $editor_context,
			context: $this->context,
		);

		$this->listener->handle( $event );

		self::assertSame( $allowed, $event->allowed );
	}

	private function make_editor_context_with_post_type( string $post_type ): WP_Block_Editor_Context {

		$post = Mockery::mock( WP_Post::class );
		$post->post_type = $post_type;

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = $post;

		return $editor_context;
	}
}
