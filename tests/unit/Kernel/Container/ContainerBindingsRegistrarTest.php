<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel\Container;

use Closure;
use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistrar;
use Fundrik\WordPress\Kernel\Container\ContainerBindingsRegistry;
use Fundrik\WordPress\Kernel\Container\ContextualBindingDefinition;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use Illuminate\Contracts\Container\ContextualBindingBuilder;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

#[CoversClass( ContainerBindingsRegistrar::class )]
final class ContainerBindingsRegistrarTest extends MockeryTestCase {

	private ContainerBindingsRegistry&MockInterface $registry;
	private LaravelContainerInterface&MockInterface $container;

	private ContainerBindingsRegistrar $registrar;

	protected function setUp(): void {

		parent::setUp();

		$this->registry = Mockery::mock( ContainerBindingsRegistry::class );
		$this->container = Mockery::mock( LaravelContainerInterface::class );

		$this->registrar = new ContainerBindingsRegistrar( $this->registry );
	}

	#[Test]
	public function it_registers_singletons_and_bindings_into_the_container(): void {

		$singletons = [
			0 => 'My\\ServiceA', // "partially keyed" style: abstract == concrete
			'My\\ContractB' => 'My\\ServiceB',
		];

		$bindings = [
			'My\\ContractC' => 'My\\ServiceC',
			'My\\ContractD' => static fn (): stdClass => new stdClass(),
		];

		$this->registry
			->shouldReceive( 'get_singletons' )
			->once()
			->andReturn( $singletons );

		$this->registry
			->shouldReceive( 'get_bindings' )
			->once()
			->andReturn( $bindings );

		$first_contextual_builder = Mockery::mock( ContextualBindingBuilder::class );
		$second_contextual_builder = Mockery::mock( ContextualBindingBuilder::class );

		$this->registry
			->shouldReceive( 'get_contextual_bindings' )
			->once()
			->andReturn(
				[
					new ContextualBindingDefinition(
						'My\\ConsumerA',
						'My\\DependencyA',
						[
							'My\\ImplementationA1',
							'My\\ImplementationA2',
						],
					),
					new ContextualBindingDefinition(
						'My\\ConsumerB',
						'$configValue',
						'configured-value',
					),
				],
			);

		$this->container
			->shouldReceive( 'singleton' )
			->once()
			->with( 'My\\ServiceA', 'My\\ServiceA' );

		$this->container
			->shouldReceive( 'singleton' )
			->once()
			->with( 'My\\ContractB', 'My\\ServiceB' );

		$this->container
			->shouldReceive( 'bind' )
			->once()
			->with( 'My\\ContractC', 'My\\ServiceC' );

		$this->container
			->shouldReceive( 'bind' )
			->once()
			->with(
				'My\\ContractD',
				Mockery::type( Closure::class ),
			);

		$this->container
			->shouldReceive( 'when' )
			->once()
			->with( 'My\\ConsumerA' )
			->andReturn( $first_contextual_builder );

		$first_contextual_builder
			->shouldReceive( 'needs' )
			->once()
			->with( 'My\\DependencyA' )
			->andReturnSelf();

		$first_contextual_builder
			->shouldReceive( 'give' )
			->once()
			->with(
				[
					'My\\ImplementationA1',
					'My\\ImplementationA2',
				],
			)
			->andReturnSelf();

		$this->container
			->shouldReceive( 'when' )
			->once()
			->with( 'My\\ConsumerB' )
			->andReturn( $second_contextual_builder );

		$second_contextual_builder
			->shouldReceive( 'needs' )
			->once()
			->with( '$configValue' )
			->andReturnSelf();

		$second_contextual_builder
			->shouldReceive( 'give' )
			->once()
			->with( 'configured-value' )
			->andReturnSelf();

		$this->registrar->register_bindings_into_container( $this->container );
	}

	#[Test]
	public function it_registers_nothing_when_registry_is_empty(): void {

		$this->registry
			->shouldReceive( 'get_singletons' )
			->once()
			->andReturn( [] );

		$this->registry
			->shouldReceive( 'get_bindings' )
			->once()
			->andReturn( [] );

		$this->registry
			->shouldReceive( 'get_contextual_bindings' )
			->once()
			->andReturn( [] );

		$this->container->shouldNotReceive( 'singleton' );
		$this->container->shouldNotReceive( 'bind' );
		$this->container->shouldNotReceive( 'when' );

		$this->registrar->register_bindings_into_container( $this->container );
	}
}
