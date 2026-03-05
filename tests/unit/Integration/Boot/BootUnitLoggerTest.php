<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot;

use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Tests\MockeryTestCase;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( BootUnitLogger::class )]
final class BootUnitLoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new BootUnitLogger( $this->psr_logger );
	}

	#[Test]
	public function it_throws_when_logging_without_setting_boot_unit_class(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage(
			'Boot unit class must be set before logging. Given: unset.',
		);

		$this->logger->log_info( 'Info entry.' );
	}

	#[Test]
	public function it_logs_debug_with_structured_context(): void {

		$this->logger->set_boot_unit_class( 'MyBootUnit' );

		$this->psr_logger
			->shouldReceive( 'debug' )
			->once()
			->with(
				'Debug entry.',
				Mockery::subset(
					[
						'service_class' => 'MyBootUnit',
						'logger_class' => BootUnitLogger::class,
						'component' => 'boot_units',
						'layer' => 'integration',
						'system' => 'wordpress',
						'operation' => 'boot',
					],
				),
			);

		$this->logger->log_debug(
			'Debug entry.',
			[
				'operation' => 'boot',
			],
		);
	}

	#[Test]
	public function it_logs_info_with_structured_context(): void {

		$this->logger->set_boot_unit_class( 'MyBootUnit' );

		$this->psr_logger
			->shouldReceive( 'info' )
			->once()
			->with(
				'Info entry.',
				Mockery::subset(
					[
						'service_class' => 'MyBootUnit',
						'logger_class' => BootUnitLogger::class,
						'component' => 'boot_units',
						'layer' => 'integration',
						'system' => 'wordpress',
						'outcome' => 'completed',
					],
				),
			);

		$this->logger->log_info(
			'Info entry.',
			[
				'outcome' => 'completed',
			],
		);
	}

	#[Test]
	public function it_logs_warning_with_structured_context(): void {

		$this->logger->set_boot_unit_class( 'MyBootUnit' );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Warning entry.',
				Mockery::subset(
					[
						'service_class' => 'MyBootUnit',
						'logger_class' => BootUnitLogger::class,
						'component' => 'boot_units',
						'layer' => 'integration',
						'system' => 'wordpress',
						'retry' => true,
					],
				),
			);

		$this->logger->log_warning(
			'Warning entry.',
			[
				'retry' => true,
			],
		);
	}

	#[Test]
	public function it_logs_error_with_structured_context_and_does_not_override_base_service_class(): void {

		$this->logger->set_boot_unit_class( 'MyBootUnit' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Error entry.',
				Mockery::subset(
					[
						'service_class' => 'MyBootUnit',
						'logger_class' => BootUnitLogger::class,
						'component' => 'boot_units',
						'layer' => 'integration',
						'system' => 'wordpress',
						'outcome' => 'failed',
					],
				),
			);

		$this->logger->log_error(
			'Error entry.',
			[
				'service_class' => 'OverriddenClassShouldBeIgnored',
				'outcome' => 'failed',
			],
		);
	}
}
