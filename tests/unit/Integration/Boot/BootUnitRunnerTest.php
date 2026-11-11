<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\BootUnitInterface;
use Fundrik\WordPress\Integration\Boot\BootUnitRunner;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( BootUnitRunner::class )]
final class BootUnitRunnerTest extends MockeryTestCase {

	#[Test]
	public function boot_all_boots_each_unit_in_configured_order(): void {

		$first_boot_unit = Mockery::mock( BootUnitInterface::class );
		$first_boot_unit->shouldReceive( 'boot' )->once()->ordered();

		$second_boot_unit = Mockery::mock( BootUnitInterface::class );
		$second_boot_unit->shouldReceive( 'boot' )->once()->ordered();

		$runner = new BootUnitRunner( $first_boot_unit, $second_boot_unit );

		$runner->boot_all();
	}
}
