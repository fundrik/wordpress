<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Kernel\Container;

use Fundrik\WordPress\Kernel\Container\RuntimeContainer;
use Fundrik\WordPress\Tests\FundrikTestCase;
use Illuminate\Container\Container as LaravelContainer;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( RuntimeContainer::class )]
final class RuntimeContainerTest extends FundrikTestCase {

	protected function setUp(): void {

		parent::setUp();

		RuntimeContainer::reset();
	}

	protected function tearDown(): void {

		RuntimeContainer::reset();

		parent::tearDown();
	}

	#[Test]
	public function get_throws_when_container_is_unavailable(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Fundrik runtime container is not available.' );

		RuntimeContainer::get();
	}

	#[Test]
	public function set_makes_the_runtime_container_available(): void {

		$container = new LaravelContainer();

		RuntimeContainer::set( $container );

		$this->assertSame( $container, RuntimeContainer::get() );
	}

	#[Test]
	public function reset_clears_the_runtime_container(): void {

		RuntimeContainer::set( new LaravelContainer() );

		RuntimeContainer::reset();

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Fundrik runtime container is not available.' );

		RuntimeContainer::get();
	}
}
