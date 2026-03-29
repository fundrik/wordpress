<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Closure;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterRestApiRoutesBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\RestApi\RestRouteInterface;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationsRestRoute;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use RuntimeException;
use WP_REST_Server;

#[CoversClass( RegisterRestApiRoutesBootUnit::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( RestApiInitActionHookDispatcher::class )]
final class RegisterRestApiRoutesBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'rest_api_init';

	private RestApiInitActionHookDispatcher $rest_api_init_hook;
	private Closure $rest_api_init_callback;
	private RestRouteInterface&MockInterface $route;
	private RegisterRestApiRoutesBootUnit $boot_unit;
	private WP_REST_Server $rest_server;
	private LoggerInterface&MockInterface $psr_logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$this->rest_api_init_hook = new RestApiInitActionHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
		$this->rest_api_init_callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->rest_api_init_hook->register( ... ),
		);

		$this->route = Mockery::mock( RestRouteInterface::class );
		$this->rest_server = Mockery::mock( WP_REST_Server::class );

		$this->boot_unit = new RegisterRestApiRoutesBootUnit(
			$this->rest_api_init_hook,
			new BootUnitLogger( $this->psr_logger ),
			$this->route,
		);
	}

	#[Test]
	public function boot_registers_all_routes_on_rest_api_init(): void {

		$this->route->shouldReceive( 'get_route_namespace' )->once()->andReturn( DonationsRestRoute::ROUTE_NAMESPACE );
		$this->route->shouldReceive( 'get_route_path' )->once()->andReturn( DonationsRestRoute::ROUTE_PATH );
		$this->route->shouldReceive( 'get_route_args' )->once()->andReturn( [ 'methods' => 'POST' ] );

		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				DonationsRestRoute::ROUTE_NAMESPACE,
				DonationsRestRoute::ROUTE_PATH,
				[ 'methods' => 'POST' ],
			)
			->andReturn( true );

		Functions\expect( 'fundrik_set_failure_message' )->never();

		$this->boot_unit->boot();

		( $this->rest_api_init_callback )( $this->rest_server );
	}

	#[Test]
	public function boot_logs_error_when_route_registration_returns_false(): void {

		$this->route->shouldReceive( 'get_route_namespace' )->once()->andReturn( DonationsRestRoute::ROUTE_NAMESPACE );
		$this->route->shouldReceive( 'get_route_path' )->once()->andReturn( DonationsRestRoute::ROUTE_PATH );
		$this->route->shouldReceive( 'get_route_args' )->once()->andReturn( [ 'methods' => 'POST' ] );

		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				DonationsRestRoute::ROUTE_NAMESPACE,
				DonationsRestRoute::ROUTE_PATH,
				[ 'methods' => 'POST' ],
			)
			->andReturn( false );

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Failed to register REST route "fundrik/v1/donations".' );

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'REST route registration failed.',
				Mockery::on(
					static function ( array $context ): bool {

						if ( ( $context['service_class'] ?? null ) !== RegisterRestApiRoutesBootUnit::class ) {
							return false;
						}

						if ( ( $context['component'] ?? null ) !== 'boot_units' ) {
							return false;
						}

						if ( ( $context['registered_count'] ?? null ) !== 0 ) {
							return false;
						}

						if ( ( $context['total_count'] ?? null ) !== 1 ) {
							return false;
						}

						return ( $context['exception'] ?? null ) instanceof RuntimeException
							&& ( $context['exception'] ?? null )->getMessage() === 'Failed to register REST route "fundrik/v1/donations".';
					},
				),
			);

		$this->boot_unit->boot();

		( $this->rest_api_init_callback )( $this->rest_server );
	}
}
