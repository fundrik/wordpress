<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminPageRegistrar::class )]
final class AdminPageRegistrarTest extends MockeryTestCase {

	#[Test]
	public function it_registers_all_admin_pages(): void {

		$first_page = Mockery::mock( AdminPageInterface::class );
		$first_page->shouldReceive( 'register' )->once();

		$second_page = Mockery::mock( AdminPageInterface::class );
		$second_page->shouldReceive( 'register' )->once();

		$registrar = new AdminPageRegistrar( $first_page, $second_page );

		$registrar->register_all();
	}

	#[Test]
	public function it_returns_the_count_of_configured_admin_pages(): void {

		$registrar = new AdminPageRegistrar(
			Mockery::mock( AdminPageInterface::class ),
			Mockery::mock( AdminPageInterface::class ),
		);

		$this->assertSame( 2, $registrar->count() );
	}
}
