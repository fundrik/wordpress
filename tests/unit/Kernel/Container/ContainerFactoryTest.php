<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel\Container;

use Fundrik\WordPress\Kernel\Container\ContainerFactory;
use Fundrik\WordPress\Tests\FundrikTestCase;
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( ContainerFactory::class )]
final class ContainerFactoryTest extends FundrikTestCase {

	private ContainerFactory $factory;

	protected function setUp(): void {

		parent::setUp();

		$this->factory = new ContainerFactory();
	}

	#[Test]
	public function create_returns_laravel_container_instance(): void {

		$container = $this->factory->create();

		$this->assertInstanceOf( LaravelContainerInterface::class, $container );
		$this->assertInstanceOf( LaravelContainer::class, $container );
	}

	#[Test]
	public function create_binds_laravel_container_interface_to_the_same_instance(): void {

		$container = $this->factory->create();

		$resolved = $container->make( LaravelContainerInterface::class );

		$this->assertSame( $container, $resolved );
	}
}
