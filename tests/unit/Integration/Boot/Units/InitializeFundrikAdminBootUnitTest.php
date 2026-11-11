<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsGroupRegistrar;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\InitializeFundrikAdminBootUnit;
use Fundrik\WordPress\Integration\Gateways\GatewayInterface;
use Fundrik\WordPress\Integration\Gateways\GatewaySettingsRegistrar;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminMenuActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Fixtures\FakeStorage;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( InitializeFundrikAdminBootUnit::class )]
#[UsesClass( Logger::class )]
#[UsesClass( AdminPageRegistrar::class )]
#[UsesClass( AdminSettingsGroupRegistrar::class )]
#[UsesClass( GatewaySettingsRegistrar::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( AdminInitActionHookDispatcher::class )]
#[UsesClass( AdminMenuActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
final class InitializeFundrikAdminBootUnitTest extends WordPressTestCase {

	private LoggerInterface&MockInterface $psr_logger;

	private AdminInitActionHookDispatcher $admin_init_hook;
	private AdminMenuActionHookDispatcher $admin_menu_hook;
	private OptionReader $option_reader;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$this->admin_init_hook = new AdminInitActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
		$this->admin_menu_hook = new AdminMenuActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
		$this->option_reader = new OptionReader( new FakeStorage() );
	}

	#[Test]
	public function boot_registers_admin_pages_and_settings_on_their_hooks(): void {

		$admin_page = Mockery::mock( AdminPageInterface::class );
		$settings_group = Mockery::mock( AdminSettingsGroupInterface::class );

		$admin_page
			->shouldReceive( 'register' )
			->once();

		$settings_group
			->shouldReceive( 'get_id' )
			->once()
			->andReturn( 'test_group' );
		$settings_group
			->shouldReceive( 'get_section_title' )
			->once()
			->andReturn( 'Test group' );
		$settings_group
			->shouldReceive( 'get_settings' )
			->once()
			->andReturn( [] );

		Functions\expect( 'add_settings_section' )
			->once();

		$boot_unit = $this->make_boot_unit(
			new AdminPageRegistrar( $admin_page ),
			new AdminSettingsGroupRegistrar( $this->option_reader, $settings_group ),
			new GatewaySettingsRegistrar( $this->option_reader ),
		);

		$boot_unit->boot();

		$this->admin_menu_hook->dispatch();
		$this->admin_init_hook->dispatch();
	}

	#[Test]
	public function boot_reports_admin_page_registration_failure(): void {

		$exception = new RuntimeException( 'Page registration failed.' );
		$admin_page = Mockery::mock( AdminPageInterface::class );
		$admin_page
			->shouldReceive( 'register' )
			->once()
			->andThrow( $exception );

		$this->expect_registration_failure( 'Admin page registration failed.', 1, $exception );

		$boot_unit = $this->make_boot_unit(
			new AdminPageRegistrar( $admin_page ),
			new AdminSettingsGroupRegistrar( $this->option_reader ),
			new GatewaySettingsRegistrar( $this->option_reader ),
		);

		$boot_unit->boot();
		$this->admin_menu_hook->dispatch();
	}

	#[Test]
	public function boot_reports_admin_settings_registration_failure(): void {

		$exception = new RuntimeException( 'Settings registration failed.' );
		$settings_group = Mockery::mock( AdminSettingsGroupInterface::class );
		$settings_group
			->shouldReceive( 'get_id' )
			->once()
			->andThrow( $exception );

		$this->expect_registration_failure( 'Admin settings registration failed.', 1, $exception );

		$boot_unit = $this->make_boot_unit(
			new AdminPageRegistrar(),
			new AdminSettingsGroupRegistrar( $this->option_reader, $settings_group ),
			new GatewaySettingsRegistrar( $this->option_reader ),
		);

		$boot_unit->boot();
		$this->admin_init_hook->dispatch();
	}

	#[Test]
	public function boot_reports_gateway_settings_registration_failure(): void {

		$exception = new RuntimeException( 'Gateway settings registration failed.' );
		$gateway = Mockery::mock( GatewayInterface::class );
		$gateway
			->shouldReceive( 'get_id' )
			->once()
			->andThrow( $exception );

		$this->expect_registration_failure( 'Admin settings registration failed.', 0, $exception );

		$boot_unit = $this->make_boot_unit(
			new AdminPageRegistrar(),
			new AdminSettingsGroupRegistrar( $this->option_reader ),
			new GatewaySettingsRegistrar( $this->option_reader, $gateway ),
		);

		$boot_unit->boot();
		$this->admin_init_hook->dispatch();
	}

	private function make_boot_unit(
		AdminPageRegistrar $admin_page_registrar,
		AdminSettingsGroupRegistrar $admin_settings_registrar,
		GatewaySettingsRegistrar $gateway_settings_registrar,
	): InitializeFundrikAdminBootUnit {

		return new InitializeFundrikAdminBootUnit(
			$this->admin_menu_hook,
			$this->admin_init_hook,
			$admin_page_registrar,
			$admin_settings_registrar,
			$gateway_settings_registrar,
			new BootUnitLogger( $this->psr_logger ),
		);
	}

	private function expect_registration_failure(
		string $message,
		int $total_count,
		RuntimeException $exception,
	): void {

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				$message,
				[
					'service_class' => InitializeFundrikAdminBootUnit::class,
					'logger_class' => BootUnitLogger::class,
					'component' => 'boot_units',
					'layer' => 'integration',
					'system' => 'wordpress',
					'total_count' => $total_count,
					'exception' => $exception,
				],
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( $exception->getMessage() );
	}
}
