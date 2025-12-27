<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel;

use Fundrik\WordPress\Kernel\Plugin;
use Fundrik\WordPress\Kernel\Ports\EventListenerRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\HookBridgeRegistrarPort;
use Fundrik\WordPress\Kernel\Ports\MigrationRunnerPort;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( Plugin::class )]
final class PluginTest extends MockeryTestCase {

	private EventListenerRegistrarPort&MockInterface $event_listener_registrar;
	private MigrationRunnerPort&MockInterface $migration_runner;
	private HookBridgeRegistrarPort&MockInterface $hook_bridge_registrar;

	private Plugin $plugin;

	protected function setUp(): void {

		parent::setUp();

		$this->event_listener_registrar = Mockery::mock( EventListenerRegistrarPort::class );
		$this->migration_runner = Mockery::mock( MigrationRunnerPort::class );
		$this->hook_bridge_registrar = Mockery::mock( HookBridgeRegistrarPort::class );

		$this->plugin = new Plugin(
			$this->event_listener_registrar,
			$this->migration_runner,
			$this->hook_bridge_registrar,
		);
	}

	#[Test]
	public function it_runs_the_plugin_bootstrap(): void {

		$this->migration_runner
			->shouldReceive( 'migrate' )
			->once();

		$this->event_listener_registrar
			->shouldReceive( 'register_all' )
			->once();

		$this->hook_bridge_registrar
			->shouldReceive( 'register_all' )
			->once();

		$this->plugin->run();
	}
}
