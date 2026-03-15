<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Closure;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\BetaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\GammaPostTypeConfig;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use stdClass;
use WP_Block_Editor_Context;

#[CoversClass( FilterAllowedBlocksByPostTypeBootUnit::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( AllowedBlockTypesAllFilterHookDispatcher::class )]
final class FilterAllowedBlocksByPostTypeBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'allowed_block_types_all';

	private AllowedBlockTypesAllFilterHookDispatcher $allowed_block_types_hook;
	private Closure $allowed_block_types_callback;

	private WordPressContextInterface&MockInterface $wp_context;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$hook_logger = new HookDispatcherLogger( $this->psr_logger );
		$this->allowed_block_types_hook = new AllowedBlockTypesAllFilterHookDispatcher( $hook_logger );
		$this->allowed_block_types_callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$this->allowed_block_types_hook->register( ... ),
		);

		$this->wp_context = Mockery::mock( WordPressContextInterface::class );

		$this->logger = new BootUnitLogger( $this->psr_logger );
	}

	#[Test]
	public function boot_attaches_filter_callback(): void {

		$boot_unit = $this->create_boot_unit();

		$boot_unit->boot();

		$post = new stdClass();
		$post->post_type = 'post';

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = $post;

		$this->wp_context
			->shouldReceive( 'get_registered_block_types' )
			->once()
			->andReturn(
				[
					'core/paragraph' => new stdClass(),
					'fundrik/free' => new stdClass(),
					'gamma/block' => new stdClass(), // restricted to post type 'gamma' (GammaPostTypeConfig)
				],
			);

		$returned = ( $this->allowed_block_types_callback )( true, $editor_context );

		self::assertSame( [ 'core/paragraph', 'fundrik/free' ], $returned );
	}

	#[Test]
	public function filter_returns_original_when_post_type_is_missing(): void {

		$boot_unit = $this->create_boot_unit();

		$boot_unit->boot();

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = null;

		$allowed = [ 'core/paragraph' ];

		$returned = ( $this->allowed_block_types_callback )( $allowed, $editor_context );

		self::assertSame( $allowed, $returned );
	}

	#[Test]
	public function filter_returns_false_and_logs_debug_when_upstream_disallows_all(): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'All blocks are disallowed by upstream filter.',
				Mockery::subset(
					[
						'service_class' => FilterAllowedBlocksByPostTypeBootUnit::class,
						'logger_class' => BootUnitLogger::class,
						'component' => 'boot_units',
						'layer' => 'integration',
						'system' => 'wordpress',
						'post_type' => 'gamma',
					],
				),
			);

		$boot_unit = $this->create_boot_unit();

		$boot_unit->boot();

		$post = new stdClass();
		$post->post_type = 'gamma';

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = $post;

		$returned = ( $this->allowed_block_types_callback )( false, $editor_context );

		self::assertFalse( $returned );
	}

	#[Test]
	public function filter_expands_true_and_filters_restricted_blocks(): void {

		$this->wp_context
			->shouldReceive( 'get_registered_block_types' )
			->once()
			->andReturn(
				[
					'core/paragraph' => new stdClass(),
					'gamma/block' => new stdClass(), // restricted to 'gamma'
					'fundrik/free' => new stdClass(), // unrestricted
				],
			);

		$boot_unit = $this->create_boot_unit();

		$boot_unit->boot();

		$post = new stdClass();
		$post->post_type = 'post';

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = $post;

		$returned = ( $this->allowed_block_types_callback )( true, $editor_context );

		self::assertSame( [ 'core/paragraph', 'fundrik/free' ], $returned );
	}

	/**
	 * Creates the boot unit with the provided post type configs.
	 *
	 * @param PostTypeConfigInterface ...$post_type_configs The post type configs used to build the block map.
	 */
	private function create_boot_unit( PostTypeConfigInterface ...$post_type_configs ): FilterAllowedBlocksByPostTypeBootUnit {

		if ( $post_type_configs === [] ) {
			$post_type_configs = [
				new AlphaPostTypeConfig(),
				new BetaPostTypeConfig(),
				new GammaPostTypeConfig(),
			];
		}

		return new FilterAllowedBlocksByPostTypeBootUnit(
			$this->allowed_block_types_hook,
			$this->wp_context,
			$this->logger,
			...$post_type_configs,
		);
	}
}
