<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\PostTypes\PostTypeRegistrar;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\PostTypeConfigFixture;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;

#[CoversClass( RegisterPostTypesBootUnit::class )]
#[UsesClass( Logger::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( InitActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
#[UsesClass( PostTypeRegistrar::class )]
final class RegisterPostTypesBootUnitTest extends WordPressTestCase {

	private RegisterPostTypesBootUnit $boot_unit;
	private InitActionHookDispatcher $init_hook;
	private LoggerInterface&MockInterface $psr_logger;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->init_hook = new InitActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
		$this->boot_unit = new RegisterPostTypesBootUnit(
			$this->init_hook,
			new PostTypeRegistrar( new PostTypeMetaFieldReader() ),
			new BootUnitLogger( $this->psr_logger ),
			new PostTypeConfigFixture( 'first' ),
			new PostTypeConfigFixture( 'second' ),
		);
	}

	#[Test]
	public function boot_registers_all_configured_post_types_on_init(): void {

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'first', Mockery::type( 'array' ) )
			->andReturn( new stdClass() );

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'second', Mockery::type( 'array' ) )
			->andReturn( new stdClass() );

		$this->boot_unit->boot();
		$this->init_hook->dispatch();
	}

	#[Test]
	public function boot_reports_post_type_registration_failure_with_progress(): void {

		$exception = new RuntimeException( 'Post type registration failed.' );

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'first', Mockery::type( 'array' ) )
			->andReturn( new stdClass() );

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'second', Mockery::type( 'array' ) )
			->andThrow( $exception );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Post type registration failed.',
				[
					'service_class' => RegisterPostTypesBootUnit::class,
					'logger_class' => BootUnitLogger::class,
					'component' => 'boot_units',
					'layer' => 'integration',
					'system' => 'wordpress',
					'registered_count' => 1,
					'total_count' => 2,
					'exception' => $exception,
				],
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( $exception->getMessage() );

		$this->boot_unit->boot();
		$this->init_hook->dispatch();
	}
}
