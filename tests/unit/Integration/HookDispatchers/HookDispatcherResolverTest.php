<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookDispatchers;

use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherInterface;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherResolver;
use Fundrik\WordPress\Kernel\Container\ContainerInterface;
use Fundrik\WordPress\Tests\Fixtures\DummyDispatcher;
use Fundrik\WordPress\Tests\MockeryTestCase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

#[CoversClass( HookDispatcherResolver::class )]
final class HookDispatcherResolverTest extends MockeryTestCase {

	private ContainerInterface&MockInterface $container;
	private HookDispatcherResolver $resolver;

	protected function setUp(): void {

		parent::setUp();

		$this->container = Mockery::mock( ContainerInterface::class );
		$this->resolver = new HookDispatcherResolver( $this->container );
	}

	#[Test]
	public function it_throws_when_class_does_not_exist(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'Cannot resolve the hook dispatcher: the class must exist. Given: NotARealDispatcher.',
		);

		$this->resolver->resolve( 'NotARealDispatcher' );
	}

	#[Test]
	public function it_throws_when_class_does_not_implement_interface(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Cannot resolve the hook dispatcher: the class must implement %s. Given: %s.',
				HookDispatcherInterface::class,
				stdClass::class,
			),
		);

		$this->resolver->resolve( stdClass::class );
	}

	#[Test]
	public function it_resolves_dispatcher_through_container(): void {

		$dispatcher = Mockery::mock( HookDispatcherInterface::class );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( DummyDispatcher::class )
			->andReturn( $dispatcher );

		$resolved = $this->resolver->resolve( DummyDispatcher::class );

		self::assertSame( $dispatcher, $resolved );
	}
}
