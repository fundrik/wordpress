<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterRestApiRoutesBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Fixtures\TestRestRoute;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestServer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass( RegisterRestApiRoutesBootUnit::class )]
#[UsesClass( Logger::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( RestApiInitActionHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
final class RegisterRestApiRoutesBootUnitTest extends WordPressTestCase {

	private const array FIRST_ROUTE_ARGS = [ [ 'methods' => 'GET' ] ];
	private const array SECOND_ROUTE_ARGS = [ [ 'methods' => 'POST' ] ];

	private RegisterRestApiRoutesBootUnit $boot_unit;
	private LoggerInterface&MockInterface $psr_logger;
	private RestApiInitActionHookDispatcher $rest_api_init_hook;
	private WpRestServer $rest_server;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->rest_api_init_hook = new RestApiInitActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
		$this->boot_unit = new RegisterRestApiRoutesBootUnit(
			$this->rest_api_init_hook,
			new BootUnitLogger( $this->psr_logger ),
			new TestRestRoute( self::FIRST_ROUTE_ARGS ),
			new TestRestRoute( self::SECOND_ROUTE_ARGS ),
		);
		$this->rest_server = new WpRestServer();
	}

	#[Test]
	public function boot_registers_all_routes_on_rest_api_init(): void {

		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'test/v1', '/test', self::FIRST_ROUTE_ARGS )
			->andReturn( true );

		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'test/v1', '/test', self::SECOND_ROUTE_ARGS )
			->andReturn( true );

		$this->boot_unit->boot();
		$this->rest_api_init_hook->dispatch( $this->rest_server );
	}

	#[Test]
	public function boot_reports_rejected_route_registration_with_progress(): void {

		$logged_context = null;

		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'test/v1', '/test', self::FIRST_ROUTE_ARGS )
			->andReturn( true );

		Functions\expect( 'register_rest_route' )
			->once()
			->with( 'test/v1', '/test', self::SECOND_ROUTE_ARGS )
			->andReturn( false );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'REST route registration failed.',
				Mockery::capture( $logged_context ),
			);

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Failed to register REST route "test/v1/test".' );

		$this->boot_unit->boot();
		$this->rest_api_init_hook->dispatch( $this->rest_server );

		self::assertIsArray( $logged_context );

		$exception = $logged_context['exception'] ?? null;

		self::assertInstanceOf( RuntimeException::class, $exception );
		self::assertSame( 'Failed to register REST route "test/v1/test".', $exception->getMessage() );
		self::assertSame(
			[
				'service_class' => RegisterRestApiRoutesBootUnit::class,
				'logger_class' => BootUnitLogger::class,
				'component' => 'boot_units',
				'layer' => 'integration',
				'system' => 'wordpress',
				'registered_count' => 1,
				'total_count' => 2,
				'exception' => $exception,
			],
			$logged_context,
		);
	}
}
