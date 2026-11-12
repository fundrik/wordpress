<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistrar;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( HookDispatcherRegistrar::class )]
final class HookDispatcherRegistrarTest extends MockeryTestCase {

	private HookDispatcherInterface&MockInterface $first_dispatcher;
	private HookDispatcherInterface&MockInterface $second_dispatcher;
	private HookDispatcherRegistrar $registrar;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->first_dispatcher = Mockery::mock( HookDispatcherInterface::class );
		$this->second_dispatcher = Mockery::mock( HookDispatcherInterface::class );
		$this->registrar = new HookDispatcherRegistrar( $this->first_dispatcher, $this->second_dispatcher );
	}

	#[Test]
	public function register_all_registers_every_configured_dispatcher(): void {

		$this->first_dispatcher
			->shouldReceive( 'register' )
			->once();

		$this->second_dispatcher
			->shouldReceive( 'register' )
			->once();

		$this->registrar->register_all();
	}
}
