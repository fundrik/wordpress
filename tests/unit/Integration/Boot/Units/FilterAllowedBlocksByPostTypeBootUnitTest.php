<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\FilterAllowedBlocksByPostTypeBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AllowedBlockTypesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigFactory;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigRegistry;
use Fundrik\WordPress\Integration\WordPressContext\WordPressContextInterface;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\BetaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\GammaPostTypeConfig;
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
#[UsesClass( PostTypeConfigFactory::class )]
final class FilterAllowedBlocksByPostTypeBootUnitTest extends WordPressTestCase {

	private AllowedBlockTypesAllFilterHookDispatcher $allowed_block_types_hook;

	private WordPressContextInterface&MockInterface $wp_context;
	private PostTypeConfigRegistry&MockInterface $post_type_config_registry;
	private PostTypeConfigFactory $post_type_config_factory;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	private FilterAllowedBlocksByPostTypeBootUnit $boot_unit;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$hook_logger = new HookDispatcherLogger( $this->psr_logger );
		$this->allowed_block_types_hook = new AllowedBlockTypesAllFilterHookDispatcher( $hook_logger );

		$this->wp_context = Mockery::mock( WordPressContextInterface::class );
		$this->post_type_config_registry = Mockery::mock( PostTypeConfigRegistry::class );

		$this->post_type_config_factory = new PostTypeConfigFactory();

		$this->logger = new BootUnitLogger( $this->psr_logger );

		$this->post_type_config_registry
			->shouldReceive( 'get_post_type_config_classes' )
			->andReturn(
				[
					AlphaPostTypeConfig::class,
					BetaPostTypeConfig::class,
					GammaPostTypeConfig::class,
				],
			);

		$this->boot_unit = new FilterAllowedBlocksByPostTypeBootUnit(
			$this->allowed_block_types_hook,
			$this->wp_context,
			$this->post_type_config_registry,
			$this->post_type_config_factory,
			$this->logger,
		);
	}

	#[Test]
	public function boot_attaches_filter_callback(): void {

		$this->boot_unit->boot();

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

		$returned = $this->allowed_block_types_hook->handle( true, $editor_context );

		self::assertSame( [ 'core/paragraph', 'fundrik/free' ], $returned );
	}

	#[Test]
	public function filter_returns_original_when_post_type_is_missing(): void {

		$this->boot_unit->boot();

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = null;

		$allowed = [ 'core/paragraph' ];

		$returned = $this->allowed_block_types_hook->handle( $allowed, $editor_context );

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

		$this->boot_unit->boot();

		$post = new stdClass();
		$post->post_type = 'gamma';

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = $post;

		$returned = $this->allowed_block_types_hook->handle( false, $editor_context );

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

		$this->boot_unit->boot();

		$post = new stdClass();
		$post->post_type = 'post';

		$editor_context = Mockery::mock( WP_Block_Editor_Context::class );
		$editor_context->post = $post;

		$returned = $this->allowed_block_types_hook->handle( true, $editor_context );

		self::assertSame( [ 'core/paragraph', 'fundrik/free' ], $returned );
	}
}
