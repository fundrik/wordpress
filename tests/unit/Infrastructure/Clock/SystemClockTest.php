<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Infrastructure\Clock;

use Fundrik\WordPress\Infrastructure\Clock\SystemClock;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( SystemClock::class )]
final class SystemClockTest extends FundrikTestCase {

	#[Test]
	public function now_returns_current_utc_time(): void {

		$before = time();
		$current_time = ( new SystemClock() )->now()->get_value();
		$after = time();

		self::assertGreaterThanOrEqual( $before, $current_time->getTimestamp() );
		self::assertLessThanOrEqual( $after, $current_time->getTimestamp() );
		self::assertSame( 'UTC', $current_time->getTimezone()->getName() );
	}
}
