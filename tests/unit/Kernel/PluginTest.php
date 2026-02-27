<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel;

use Fundrik\WordPress\Kernel\Plugin;
use Fundrik\WordPress\Kernel\Ports\BootUnitRunnerPort;
use Fundrik\WordPress\Kernel\Ports\HookDispatcherRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

#[CoversClass( Plugin::class )]
final class PluginTest extends MockeryTestCase {

	private MigrationRunnerPort&MockInterface $migration_runner;
	private HookDispatcherRegistrarPort&MockInterface $hook_dispatcher_registrar;
	private BootUnitRunnerPort&MockInterface $boot_unit_runner;

	private Plugin $plugin;

	protected function setUp(): void {

		parent::setUp();

		$this->migration_runner = Mockery::mock( MigrationRunnerPort::class );
		$this->hook_dispatcher_registrar = Mockery::mock( HookDispatcherRegistrarPort::class );
		$this->boot_unit_runner = Mockery::mock( BootUnitRunnerPort::class );

		$this->plugin = new Plugin(
			$this->migration_runner,
			$this->hook_dispatcher_registrar,
			$this->boot_unit_runner,
		);
	}

	#[Test]
	public function it_runs_migrations_and_then_boots_wordpress_integration_in_order(): void {

		$this->migration_runner
			->shouldReceive( 'migrate' )
			->once()
			->ordered();

		$this->hook_dispatcher_registrar
			->shouldReceive( 'register_all' )
			->once()
			->ordered();

		$this->boot_unit_runner
			->shouldReceive( 'boot_all' )
			->once()
			->ordered();

		$this->plugin->run();

		$this->addToAssertionCount( 1 );
	}

	#[Test]
	public function it_does_not_boot_wordpress_integration_when_migration_fails(): void {

		$e = new RuntimeException( 'boom' );

		$this->migration_runner
			->shouldReceive( 'migrate' )
			->once()
			->andThrow( $e );

		$this->hook_dispatcher_registrar->shouldNotReceive( 'register_all' );
		$this->boot_unit_runner->shouldNotReceive( 'boot_all' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'boom' );

		$this->plugin->run();
	}
}
