<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminPages;

use Fundrik\WordPress\Integration\AdminPages\AdminPageInterface;
use Fundrik\WordPress\Integration\AdminPages\AdminPageRegistrar;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminPageRegistrar::class )]
final class AdminPageRegistrarTest extends MockeryTestCase {

	private AdminPageInterface&MockInterface $first_page;
	private AdminPageInterface&MockInterface $second_page;
	private AdminPageRegistrar $registrar;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->first_page = Mockery::mock( AdminPageInterface::class );
		$this->second_page = Mockery::mock( AdminPageInterface::class );
		$this->registrar = new AdminPageRegistrar( $this->first_page, $this->second_page );
	}

	#[Test]
	public function register_all_registers_every_configured_admin_page(): void {

		$this->first_page
			->shouldReceive( 'register' )
			->once();

		$this->second_page
			->shouldReceive( 'register' )
			->once();

		$this->registrar->register_all();
	}

	#[Test]
	public function count_returns_number_of_configured_admin_pages(): void {

		self::assertSame( 2, $this->registrar->count() );
	}
}
