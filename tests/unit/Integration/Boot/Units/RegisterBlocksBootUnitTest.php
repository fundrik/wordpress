<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksBootUnit;
use Fundrik\WordPress\Integration\Helpers\PluginPath;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\BlockCategoriesAllFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpBlockEditorContext;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( RegisterBlocksBootUnit::class )]
#[UsesClass( Logger::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( PluginPath::class )]
#[UsesClass( BlockCategoriesAllFilterHookDispatcher::class )]
#[UsesClass( InitActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
final class RegisterBlocksBootUnitTest extends WordPressTestCase {

	private RegisterBlocksBootUnit $boot_unit;
	private BlockCategoriesAllFilterHookDispatcher $block_categories_hook;
	private InitActionHookDispatcher $init_hook;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$psr_logger = Mockery::mock( LoggerInterface::class );
		$this->init_hook = new InitActionHookDispatcher(
			new HookDispatcherLogger( $psr_logger ),
		);
		$this->block_categories_hook = new BlockCategoriesAllFilterHookDispatcher(
			new HookDispatcherLogger( $psr_logger ),
		);
		$this->boot_unit = new RegisterBlocksBootUnit(
			$this->init_hook,
			$this->block_categories_hook,
			new BootUnitLogger( $psr_logger ),
		);
	}

	#[Test]
	public function boot_registers_block_metadata_collection_on_init(): void {

		Functions\expect( 'wp_register_block_types_from_metadata_collection' )
			->once()
			->with(
				PluginPath::Blocks->get_full_path(),
				PluginPath::BlocksManifest->get_full_path(),
			);

		$this->boot_unit->boot();
		$this->init_hook->dispatch();
	}

	#[Test]
	public function boot_appends_fundrik_block_category(): void {

		$categories = [
			[
				'slug' => 'widgets',
				'title' => 'Widgets',
			],
		];

		$this->boot_unit->boot();

		self::assertSame(
			[
				...$categories,
				[
					'slug' => 'fundrik',
					'title' => 'Fundrik',
				],
			],
			$this->block_categories_hook->dispatch(
				$categories,
				new WpBlockEditorContext(),
			),
		);
	}

	#[Test]
	public function boot_does_not_duplicate_existing_fundrik_block_category(): void {

		$categories = [
			[
				'slug' => 'fundrik',
				'title' => 'Custom Fundrik title',
			],
			[
				'slug' => 'widgets',
				'title' => 'Widgets',
			],
		];

		$this->boot_unit->boot();

		self::assertSame(
			$categories,
			$this->block_categories_hook->dispatch(
				$categories,
				new WpBlockEditorContext(),
			),
		);
	}
}
