<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\HookToEventBridges;

use Fundrik\WordPress\Bootstrap\Container\ContainerInterface;
use Fundrik\WordPress\Integration\HookToEventBridges\HookBridgeRegistrar;
use Fundrik\WordPress\Integration\HookToEventBridges\HookBridgeRegistry;
use Fundrik\WordPress\Integration\HookToEventBridges\HookToEventBridgeInterface;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use stdClass;

#[CoversClass( HookBridgeRegistrar::class )]
final class HookBridgeRegistrarTest extends MockeryTestCase {

	private HookBridgeRegistry&MockInterface $registry;
	private ContainerInterface&MockInterface $container;
	private HookBridgeRegistrar $registrar;

	protected function setUp(): void {

		parent::setUp();

		$this->registry = Mockery::mock( HookBridgeRegistry::class );
		$this->container = Mockery::mock( ContainerInterface::class );

		$this->registrar = new HookBridgeRegistrar( $this->registry, $this->container );
	}

	#[Test]
	public function it_registers_all_bridge_classes(): void {

		$bridge1 = Mockery::mock( HookToEventBridgeInterface::class );
		$bridge2 = Mockery::mock( HookToEventBridgeInterface::class );

		$class1 = new class() implements HookToEventBridgeInterface {

			public function register(): void {} // phpcs:ignore SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction
		};

		$class2 = new class() implements HookToEventBridgeInterface {

			public function register(): void {} // phpcs:ignore SlevomatCodingStandard.Functions.DisallowEmptyFunction.EmptyFunction
		};

		$class1_name = $class1::class;
		$class2_name = $class2::class;

		$this->registry
			->shouldReceive( 'get_bridge_classes' )
			->once()
			->andReturn( [ $class1_name, $class2_name ] );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( $class1_name )
			->andReturn( $bridge1 );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( $class2_name )
			->andReturn( $bridge2 );

		$bridge1->shouldReceive( 'register' )->once();
		$bridge2->shouldReceive( 'register' )->once();

		$this->registrar->register_all();
	}

	#[Test]
	public function it_throws_when_registry_contains_invalid_bridge_class(): void {

		$invalid_class = stdClass::class;

		$this->registry
			->shouldReceive( 'get_bridge_classes' )
			->once()
			->andReturn( [ $invalid_class ] );

		$this->container->shouldNotReceive( 'make' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage(
			sprintf(
				'Hook bridge must implement %s. Given: %s.',
				HookToEventBridgeInterface::class,
				$invalid_class,
			),
		);

		$this->registrar->register_all();
	}
}
