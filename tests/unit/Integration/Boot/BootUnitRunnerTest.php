<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitRegistry;
use Fundrik\WordPress\Integration\Boot\BootUnitResolver;
use Fundrik\WordPress\Integration\Boot\BootUnitRunner;
use Fundrik\WordPress\Kernel\Container\ContainerInterface;
use Fundrik\WordPress\Tests\Fixtures\DummyBootUnit;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( BootUnitRunner::class )]
#[UsesClass( BootUnitResolver::class )]
final class BootUnitRunnerTest extends MockeryTestCase {

	#[Test]
	public function it_boots_all_units(): void {

		$container = Mockery::mock( ContainerInterface::class );
		$resolver = new BootUnitResolver( $container );

		$class_name = DummyBootUnit::class;
		$boot_unit = Mockery::mock( BootUnitInterface::class );
		$boot_unit
			->shouldReceive( 'boot' )
			->once();

		$registry = Mockery::mock( BootUnitRegistry::class );
		$registry
			->shouldReceive( 'get_boot_unit_classes' )
			->once()
			->andReturn( [ $class_name ] );

		$container
			->shouldReceive( 'make' )
			->once()
			->with( $class_name )
			->andReturn( $boot_unit );

		$runner = new BootUnitRunner( $registry, $resolver );

		$runner->boot_all();
	}
}
