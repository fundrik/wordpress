<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistrar;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherRegistry;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherResolver;
use Fundrik\WordPress\Kernel\Container\ContainerInterface;
use Fundrik\WordPress\Tests\Fixtures\DummyDispatcher;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( HookDispatcherRegistrar::class )]
#[UsesClass( HookDispatcherResolver::class )]
final class HookDispatcherRegistrarTest extends MockeryTestCase {

	#[Test]
	public function it_registers_all_dispatchers(): void {

		$container = Mockery::mock( ContainerInterface::class );
		$resolver = new HookDispatcherResolver( $container );

		$dispatcher = Mockery::mock( HookDispatcherInterface::class );
		$dispatcher
			->shouldReceive( 'register' )
			->once();

		$registry = Mockery::mock( HookDispatcherRegistry::class );
		$registry
			->shouldReceive( 'get_dispatcher_classes' )
			->once()
			->andReturn( [ DummyDispatcher::class ] );

		$container
			->shouldReceive( 'make' )
			->once()
			->with( DummyDispatcher::class )
			->andReturn( $dispatcher );

		$registrar = new HookDispatcherRegistrar( $registry, $resolver );

		$registrar->register_all();
	}
}
