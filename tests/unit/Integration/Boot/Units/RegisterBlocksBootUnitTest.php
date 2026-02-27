<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Helpers\PluginPath;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterBlocksBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
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
#[UsesClass( InitActionHookDispatcher::class )]
#[UsesClass( PluginPath::class )]
final class RegisterBlocksBootUnitTest extends WordPressTestCase {

	private InitActionHookDispatcher $init_hook;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	private RegisterBlocksBootUnit $boot_unit;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$hook_logger = new HookDispatcherLogger( $this->psr_logger );
		$this->init_hook = new InitActionHookDispatcher( $hook_logger );

		$this->logger = new BootUnitLogger( $this->psr_logger );

		$this->boot_unit = new RegisterBlocksBootUnit( $this->init_hook, $this->logger );
	}

	#[Test]
	public function boot_attaches_callback_that_registers_blocks_and_logs_info(): void {

		$this->boot_unit->boot();

		$blocks_path = PluginPath::Blocks->get_full_path();
		$manifest_path = PluginPath::BlocksManifest->get_full_path();

		Functions\expect( 'wp_register_block_types_from_metadata_collection' )
			->once()
			->with( $blocks_path, $manifest_path );

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Registering block types completed.',
				Mockery::subset(
					[
						'service_class' => RegisterBlocksBootUnit::class,
						'component' => 'boot_units',
						'blocks_path' => $blocks_path,
						'manifest_path' => $manifest_path,
					],
				),
			);

		$this->init_hook->handle();
	}
}
