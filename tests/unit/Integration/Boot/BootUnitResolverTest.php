<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitResolver;
use Fundrik\WordPress\Kernel\Container\ContainerInterface;
use Fundrik\WordPress\Tests\Fixtures\DummyBootUnit;
use Fundrik\WordPress\Tests\MockeryTestCase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

#[CoversClass( BootUnitResolver::class )]
final class BootUnitResolverTest extends MockeryTestCase {

	private ContainerInterface&MockInterface $container;
	private BootUnitResolver $resolver;

	protected function setUp(): void {

		parent::setUp();

		$this->container = Mockery::mock( ContainerInterface::class );
		$this->resolver = new BootUnitResolver( $this->container );
	}

	#[Test]
	public function it_throws_when_class_does_not_exist(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Boot unit class must exist. Given: NotARealBootUnit.' );

		$this->resolver->resolve( 'NotARealBootUnit' );
	}

	#[Test]
	public function it_throws_when_class_does_not_implement_interface(): void {

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Boot unit class must implement %s. Given: %s.',
				BootUnitInterface::class,
				stdClass::class,
			),
		);

		$this->resolver->resolve( stdClass::class );
	}

	#[Test]
	public function it_resolves_boot_unit_through_container(): void {

		$class_name = DummyBootUnit::class;
		$boot_unit = Mockery::mock( BootUnitInterface::class );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( $class_name )
			->andReturn( $boot_unit );

		$resolved = $this->resolver->resolve( $class_name );

		self::assertSame( $boot_unit, $resolved );
	}
}
