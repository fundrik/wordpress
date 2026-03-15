<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistrar;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( HookDispatcherRegistrar::class )]
final class HookDispatcherRegistrarTest extends MockeryTestCase {

	#[Test]
	public function it_registers_all_dispatchers(): void {

		$first_dispatcher = Mockery::mock( HookDispatcherInterface::class );
		$first_dispatcher
			->shouldReceive( 'register' )
			->once();

		$second_dispatcher = Mockery::mock( HookDispatcherInterface::class );
		$second_dispatcher
			->shouldReceive( 'register' )
			->once();

		$registrar = new HookDispatcherRegistrar( $first_dispatcher, $second_dispatcher );

		$registrar->register_all();
	}
}
