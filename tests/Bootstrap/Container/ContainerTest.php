<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Bootstrap\Container;

use Fundrik\WordPress\Bootstrap\Container\Container;
use Fundrik\WordPress\Bootstrap\Container\ContainerException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

#[CoversClass( Container::class )]
final class ContainerTest extends MockeryTestCase {

	private Container $container;
	private LaravelContainerInterface&MockInterface $inner;

	protected function setUp(): void {

		parent::setUp();

		$this->inner = Mockery::mock( LaravelContainerInterface::class );
		$this->container = new Container( $this->inner );
	}

	// make()
	// ---------------------------------------------------------------------

	#[Test]
	public function make_delegates_to_inner_container_without_parameters(): void {

		$instance = new stdClass();

		$this->inner
			->shouldReceive( 'make' )
			->once()
			->with(
				$this->identicalTo( $instance::class ),
				[],
			)
			->andReturn( $instance );

		$result = $this->container->make( $instance::class );

		$this->assertSame( $instance, $result );
	}

	#[Test]
	public function make_delegates_to_inner_container_with_parameters(): void {

		$instance = new stdClass();

		$params = [
			'id' => 123,
			'name' => 'Test',
		];

		$this->inner
			->shouldReceive( 'make' )
			->once()
			->with(
				$this->identicalTo( $instance::class ),
				$this->identicalTo( $params ),
			)
			->andReturn( $instance );

		$result = $this->container->make( $instance::class, $params );

		$this->assertSame( $instance, $result );
	}

	#[Test]
	public function make_wraps_binding_resolution_exception_into_container_exception(): void {

		$e = new BindingResolutionException( 'Nope' );

		$this->inner
			->shouldReceive( 'make' )
			->once()
			->with( 'MyClass', [] )
			->andThrow( $e );

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Cannot resolve dependency: MyClass.' );
		$this->expectExceptionCode( 0 );

		try {
			$this->container->make( 'MyClass' );
		} catch ( ContainerException $ex ) {
			$this->assertSame( $e, $ex->getPrevious() );
			throw $ex;
		}
	}

	#[Test]
	public function make_throws_if_created_instance_does_not_match_expected_type(): void {

		$this->inner
			->shouldReceive( 'make' )
			->once()
			->with( 'MyClass', [] )
			->andReturn( new stdClass() );

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'The resolved service must be an instance of MyClass. Given: stdClass.' );

		$this->container->make( 'MyClass' );
	}

	// bind()
	// ---------------------------------------------------------------------

	#[Test]
	public function bind_registers_closure_binding(): void {

		$closure = static fn (): stdClass => new stdClass();

		$this->inner
			->shouldReceive( 'bind' )
			->once()
			->with(
				'MyService',
				$this->identicalTo( $closure ),
			);

		$this->container->bind( 'MyService', $closure );
	}

	#[Test]
	public function bind_registers_string_binding(): void {

		$this->inner
			->shouldReceive( 'bind' )
			->once()
			->with( 'MyService', 'MyImplementation' );

		$this->container->bind( 'MyService', 'MyImplementation' );
	}

	#[Test]
	public function bind_registers_self_binding_when_null(): void {

		$this->inner
			->shouldReceive( 'bind' )
			->once()
			->with( 'MyService', null );

		$this->container->bind( 'MyService' );
	}

	// singleton()
	// ---------------------------------------------------------------------

	#[Test]
	public function singleton_registers_closure_binding(): void {

		$closure = static fn (): stdClass => new stdClass();

		$this->inner
			->shouldReceive( 'singleton' )
			->once()
			->with(
				'MyService',
				$this->identicalTo( $closure ),
			);

		$this->container->singleton( 'MyService', $closure );
	}

	#[Test]
	public function singleton_registers_string_binding(): void {

		$this->inner
			->shouldReceive( 'singleton' )
			->once()
			->with( 'MyService', 'MyImplementation' );

		$this->container->singleton( 'MyService', 'MyImplementation' );
	}

	#[Test]
	public function singleton_registers_self_binding_when_null(): void {

		$this->inner
			->shouldReceive( 'singleton' )
			->once()
			->with( 'MyService', null );

		$this->container->singleton( 'MyService' );
	}

	// instance()
	// ---------------------------------------------------------------------

	#[Test]
	public function instance_registers_existing_instance_and_returns_it(): void {

		$instance = new stdClass();

		$this->inner
			->shouldReceive( 'instance' )
			->once()
			->with( stdClass::class, $this->identicalTo( $instance ) );

		$result = $this->container->instance( stdClass::class, $instance );

		$this->assertSame( $instance, $result );
	}

	#[Test]
	public function instance_throws_when_instance_does_not_match_expected_type(): void {

		$instance = new stdClass();

		$this->inner->shouldNotReceive( 'instance' );

		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'The registered instance must be an instance of MyService. Given: stdClass.' );

		$this->container->instance( 'MyService', $instance );
	}
}
