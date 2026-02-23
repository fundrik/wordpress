<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel\Container;

use Fundrik\WordPress\Kernel\Container\Container;
use Fundrik\WordPress\Kernel\Container\ContainerFactory;
use Fundrik\WordPress\Kernel\Container\ContainerInterface;
use Fundrik\WordPress\Tests\FundrikTestCase;
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( ContainerFactory::class )]
#[UsesClass( Container::class )]
final class ContainerFactoryTest extends FundrikTestCase {

	private ContainerFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new ContainerFactory();
	}

	#[Test]
	public function create_returns_container_instance(): void {

		$container = $this->factory->create();

		$this->assertInstanceOf( ContainerInterface::class, $container );
		$this->assertInstanceOf( Container::class, $container );
	}

	#[Test]
	public function create_binds_container_interface_to_the_same_instance(): void {

		$container = $this->factory->create();

		$resolved = $container->make( ContainerInterface::class );

		$this->assertSame( $container, $resolved );
	}

	#[Test]
	public function create_binds_laravel_container_interface_and_uses_the_same_instance_everywhere(): void {

		$container = $this->factory->create();

		$laravel = $container->make( LaravelContainerInterface::class );

		$this->assertInstanceOf( LaravelContainer::class, $laravel );

		// The adapter binds the same underlying Laravel container instance.
		$resolved_from_laravel = $laravel->make( LaravelContainerInterface::class );

		$this->assertSame( $laravel, $resolved_from_laravel );
	}

	#[Test]
	public function create_uses_the_same_laravel_container_instance_inside_the_laravel_container_itself(): void {

		$container = $this->factory->create();

		$laravel = $container->make( LaravelContainerInterface::class );

		$resolved_laravel_self = $laravel->make( LaravelContainerInterface::class );

		$this->assertSame( $laravel, $resolved_laravel_self );
	}
}
