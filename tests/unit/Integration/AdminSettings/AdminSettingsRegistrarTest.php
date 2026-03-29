<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\AdminSettings;

use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsInterface;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsRegistrar;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( AdminSettingsRegistrar::class )]
final class AdminSettingsRegistrarTest extends MockeryTestCase {

	#[Test]
	public function it_registers_all_admin_settings(): void {

		$first_settings = Mockery::mock( AdminSettingsInterface::class );
		$first_settings->shouldReceive( 'register' )->once();

		$second_settings = Mockery::mock( AdminSettingsInterface::class );
		$second_settings->shouldReceive( 'register' )->once();

		$registrar = new AdminSettingsRegistrar( $first_settings, $second_settings );

		$registrar->register_all();
	}

	#[Test]
	public function it_returns_the_count_of_configured_admin_settings(): void {

		$registrar = new AdminSettingsRegistrar(
			Mockery::mock( AdminSettingsInterface::class ),
			Mockery::mock( AdminSettingsInterface::class ),
		);

		$this->assertSame( 2, $registrar->count() );
	}
}
