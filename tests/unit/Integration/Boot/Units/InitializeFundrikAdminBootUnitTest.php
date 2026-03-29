<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Closure;
use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsRegistrar;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\InitializeFundrikAdminBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\AdminMenuActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( InitializeFundrikAdminBootUnit::class )]
#[UsesClass( AdminInitActionHookDispatcher::class )]
#[UsesClass( AdminMenuActionHookDispatcher::class )]
#[UsesClass( AdminPageRegistrar::class )]
#[UsesClass( AdminSettingsRegistrar::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
final class InitializeFundrikAdminBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string ADMIN_INIT_HOOK_NAME = 'admin_init';
	private const string ADMIN_MENU_HOOK_NAME = 'admin_menu';

	private AdminMenuActionHookDispatcher $admin_menu_hook;
	private AdminInitActionHookDispatcher $admin_init_hook;
	private Closure $admin_menu_callback;
	private Closure $admin_init_callback;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$hook_logger = new HookDispatcherLogger( $this->psr_logger );

		$this->admin_menu_hook = new AdminMenuActionHookDispatcher( $hook_logger );
		$this->admin_menu_callback = $this->register_and_capture_action_callback(
			self::ADMIN_MENU_HOOK_NAME,
			$this->admin_menu_hook->register( ... ),
		);

		$this->admin_init_hook = new AdminInitActionHookDispatcher( $hook_logger );
		$this->admin_init_callback = $this->register_and_capture_action_callback(
			self::ADMIN_INIT_HOOK_NAME,
			$this->admin_init_hook->register( ... ),
		);

		$this->logger = new BootUnitLogger( $this->psr_logger );
	}

	#[Test]
	public function boot_attaches_callbacks_that_register_admin_pages_and_settings(): void {

		$this->expect_failure_message_never();

		$first_page = Mockery::mock( AdminPageInterface::class );
		$first_page->shouldReceive( 'register' )->once()->ordered();

		$second_page = Mockery::mock( AdminPageInterface::class );
		$second_page->shouldReceive( 'register' )->once()->ordered();

		$first_settings = Mockery::mock( AdminSettingsInterface::class );
		$first_settings->shouldReceive( 'register' )->once()->ordered();

		$second_settings = Mockery::mock( AdminSettingsInterface::class );
		$second_settings->shouldReceive( 'register' )->once()->ordered();

		$boot_unit = new InitializeFundrikAdminBootUnit(
			$this->admin_menu_hook,
			$this->admin_init_hook,
			new AdminPageRegistrar( $first_page, $second_page ),
			new AdminSettingsRegistrar( $first_settings, $second_settings ),
			$this->logger,
		);

		$boot_unit->boot();

		( $this->admin_menu_callback )();
		( $this->admin_init_callback )();
	}

	#[Test]
	public function boot_logs_error_and_sets_failure_message_when_admin_page_registration_fails(): void {

		$this->expect_failure_message_once();

		$first_page = Mockery::mock( AdminPageInterface::class );
		$first_page->shouldReceive( 'register' )->once()->ordered();

		$second_page = Mockery::mock( AdminPageInterface::class );
		$second_page
			->shouldReceive( 'register' )
			->once()
			->ordered()
			->andThrow( new RuntimeException( 'Boom' ) );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Admin page registration failed.',
				Mockery::on(
					static function ( array $context ): bool {

						if ( ( $context['service_class'] ?? null ) !== InitializeFundrikAdminBootUnit::class ) {
							return false;
						}

						if ( ( $context['component'] ?? null ) !== 'boot_units' ) {
							return false;
						}

						if ( ( $context['total_count'] ?? null ) !== 2 ) {
							return false;
						}

						$e = $context['exception'] ?? null;

						return $e instanceof RuntimeException
							&& $e->getMessage() === 'Boom';
					},
				),
			);

		$boot_unit = new InitializeFundrikAdminBootUnit(
			$this->admin_menu_hook,
			$this->admin_init_hook,
			new AdminPageRegistrar( $first_page, $second_page ),
			new AdminSettingsRegistrar(),
			$this->logger,
		);

		$boot_unit->boot();

		( $this->admin_menu_callback )();
	}

	#[Test]
	public function boot_logs_error_and_sets_failure_message_when_admin_settings_registration_fails(): void {

		$this->expect_failure_message_once();

		$first_settings = Mockery::mock( AdminSettingsInterface::class );
		$first_settings->shouldReceive( 'register' )->once()->ordered();

		$second_settings = Mockery::mock( AdminSettingsInterface::class );
		$second_settings
			->shouldReceive( 'register' )
			->once()
			->ordered()
			->andThrow( new RuntimeException( 'Boom' ) );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Admin settings registration failed.',
				Mockery::on(
					static function ( array $context ): bool {

						if ( ( $context['service_class'] ?? null ) !== InitializeFundrikAdminBootUnit::class ) {
							return false;
						}

						if ( ( $context['component'] ?? null ) !== 'boot_units' ) {
							return false;
						}

						if ( ( $context['total_count'] ?? null ) !== 2 ) {
							return false;
						}

						$e = $context['exception'] ?? null;

						return $e instanceof RuntimeException
							&& $e->getMessage() === 'Boom';
					},
				),
			);

		$boot_unit = new InitializeFundrikAdminBootUnit(
			$this->admin_menu_hook,
			$this->admin_init_hook,
			new AdminPageRegistrar(),
			new AdminSettingsRegistrar( $first_settings, $second_settings ),
			$this->logger,
		);

		$boot_unit->boot();

		( $this->admin_init_callback )();
	}
}
