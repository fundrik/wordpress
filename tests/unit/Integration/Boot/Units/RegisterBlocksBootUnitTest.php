<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Closure;
use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\BlockCategoriesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( RegisterBlocksBootUnit::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( BlockCategoriesAllFilterHookDispatcher::class )]
#[UsesClass( InitActionHookDispatcher::class )]
#[UsesClass( PluginPath::class )]
final class RegisterBlocksBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'init';
	private const string CATEGORIES_HOOK_NAME = 'block_categories_all';

	private InitActionHookDispatcher $init_hook;
	private Closure $init_callback;
	private BlockCategoriesAllFilterHookDispatcher $block_categories_hook;
	private Closure $block_categories_callback;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	private RegisterBlocksBootUnit $boot_unit;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$hook_logger = new HookDispatcherLogger( $this->psr_logger );
		$this->init_hook = new InitActionHookDispatcher( $hook_logger );
		$this->init_callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->init_hook->register( ... ),
		);
		$this->block_categories_hook = new BlockCategoriesAllFilterHookDispatcher( $hook_logger );
		$this->block_categories_callback = $this->register_and_capture_filter_callback(
			self::CATEGORIES_HOOK_NAME,
			$this->block_categories_hook->register( ... ),
		);

		$this->logger = new BootUnitLogger( $this->psr_logger );

		$this->boot_unit = new RegisterBlocksBootUnit( $this->init_hook, $this->block_categories_hook, $this->logger );
	}

	#[Test]
	public function boot_attaches_callback_that_registers_blocks(): void {

		$this->boot_unit->boot();

		$blocks_path = PluginPath::Blocks->get_full_path();
		$manifest_path = PluginPath::BlocksManifest->get_full_path();

		Functions\expect( 'wp_register_block_types_from_metadata_collection' )
			->once()
			->with( $blocks_path, $manifest_path );

		( $this->init_callback )();
	}

	#[Test]
	public function boot_attaches_callback_that_registers_the_fundrik_category(): void {

		$this->boot_unit->boot();

		$editor_context = Mockery::mock( \WP_Block_Editor_Context::class );

		$returned = ( $this->block_categories_callback )(
			[
				[
					'slug' => 'widgets',
					'title' => 'Widgets',
				],
			],
			$editor_context,
		);

		self::assertSame(
			[
				[
					'slug' => 'widgets',
					'title' => 'Widgets',
				],
				[
					'slug' => 'fundrik',
					'title' => 'Fundrik',
				],
			],
			$returned,
		);
	}
}
