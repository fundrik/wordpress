<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( BootUnitLogger::class )]
#[UsesClass( Logger::class )]
final class BootUnitLoggerTest extends MockeryTestCase {

	private const string BOOT_UNIT_CLASS = 'Fundrik\Example\BootUnit';

	private BootUnitLogger $logger;
	private LoggerInterface&MockInterface $psr_logger;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new BootUnitLogger( $this->psr_logger );
	}

	#[Test]
	public function set_boot_unit_class_adds_boot_unit_context_to_log_entries(): void {

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Booting unit.',
				[
					'service_class' => self::BOOT_UNIT_CLASS,
					'logger_class' => BootUnitLogger::class,
					'component' => 'boot_units',
					'layer' => 'integration',
					'system' => 'wordpress',
					'operation' => 'boot',
				],
			);

		$this->logger->set_boot_unit_class( self::BOOT_UNIT_CLASS );
		$this->logger->log_debug( 'Booting unit.', [ 'operation' => 'boot' ] );
	}

	#[Test]
	public function logging_throws_when_boot_unit_class_is_not_set(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Boot unit class must be set before logging. Given: unset.' );

		$this->logger->log_info( 'Booting unit.' );
	}
}
