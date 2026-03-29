<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Closure;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterAdminPagesBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminMenuActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( RegisterAdminPagesBootUnit::class )]
#[UsesClass( AdminMenuActionHookDispatcher::class )]
#[UsesClass( AdminPageRegistrar::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
final class RegisterAdminPagesBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'admin_menu';

	private AdminMenuActionHookDispatcher $admin_menu_hook;
	private Closure $admin_menu_callback;

	protected function setUp(): void {

		parent::setUp();

		$hook_logger = new HookDispatcherLogger( Mockery::mock( \Psr\Log\LoggerInterface::class ) );
		$this->admin_menu_hook = new AdminMenuActionHookDispatcher( $hook_logger );
		$this->admin_menu_callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->admin_menu_hook->register( ... ),
		);
	}

	#[Test]
	public function boot_attaches_callback_that_registers_all_admin_pages(): void {

		$this->expect_failure_message_never();

		$first_page = Mockery::mock( AdminPageInterface::class );
		$first_page->shouldReceive( 'register' )->once()->ordered();

		$second_page = Mockery::mock( AdminPageInterface::class );
		$second_page->shouldReceive( 'register' )->once()->ordered();

		$boot_unit = new RegisterAdminPagesBootUnit(
			$this->admin_menu_hook,
			new AdminPageRegistrar( $first_page, $second_page ),
			new BootUnitLogger( Mockery::mock( \Psr\Log\LoggerInterface::class ) ),
		);

		$boot_unit->boot();

		( $this->admin_menu_callback )();
	}
}
