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
	public function it_boots_all_units(): void {

		$first_boot_unit = Mockery::mock( BootUnitInterface::class );
		$first_boot_unit
			->shouldReceive( 'boot' )
			->once();

		$second_boot_unit = Mockery::mock( BootUnitInterface::class );
		$second_boot_unit
			->shouldReceive( 'boot' )
			->once();

		$runner = new BootUnitRunner( $first_boot_unit, $second_boot_unit );

		$runner->boot_all();
	}
}
