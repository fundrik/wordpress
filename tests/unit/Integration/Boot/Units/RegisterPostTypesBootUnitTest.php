<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Closure;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterPostTypesBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\InitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\PostTypes\Exceptions\PostTypeRegistrationException;
use Fundrik\WordPress\Integration\PostTypes\PostTypeConfigInterface;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaField;
use Fundrik\WordPress\Integration\PostTypes\PostTypeMetaFieldReader;
use Fundrik\WordPress\Integration\PostTypes\PostTypeRegistrar;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\AlphaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\BetaPostTypeConfig;
use Fundrik\WordPress\Tests\Fixtures\PostTypes\GammaPostTypeConfig;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use stdClass;
use WP_Error;

#[CoversClass( RegisterPostTypesBootUnit::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( InitActionHookDispatcher::class )]
#[UsesClass( PostTypeRegistrar::class )]
#[UsesClass( PostTypeMetaField::class )]
#[UsesClass( PostTypeMetaFieldReader::class )]
final class RegisterPostTypesBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'init';

	private InitActionHookDispatcher $init_hook;
	private Closure $init_callback;
	private PostTypeRegistrar $post_type_registrar;

	private LoggerInterface&MockInterface $psr_logger;
	private BootUnitLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );

		$hook_logger = new HookDispatcherLogger( $this->psr_logger );
		$this->init_hook = new InitActionHookDispatcher( $hook_logger );
		$this->init_callback = $this->register_and_capture_action_callback(
			self::HOOK_NAME,
			$this->init_hook->register( ... ),
		);

		$meta_reader = new PostTypeMetaFieldReader();
		$this->post_type_registrar = new PostTypeRegistrar( $meta_reader );

		$this->logger = new BootUnitLogger( $this->psr_logger );
	}

	#[Test]
	public function boot_attaches_callback_that_registers_all_post_types(): void {

		$boot_unit = $this->create_boot_unit(
			new AlphaPostTypeConfig(),
			new BetaPostTypeConfig(),
			new GammaPostTypeConfig(),
		);

		Functions\expect( 'apply_filters' )
			->times( 6 )
			->andReturnUsing(
				// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
				static fn ( string $hook_name, mixed $value ): mixed => $value,
			);

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				'alpha',
				Mockery::on(
					static fn ( array $args ): bool => ( $args['show_in_rest'] ?? null ) === true
							&& ( $args['public'] ?? null ) === true
							&& ( $args['rewrite']['slug'] ?? null ) === 'alpha',
				),
			)
			->andReturn( new stdClass() );

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				'alpha',
				'alpha_has_nested',
				Mockery::on(
					static fn ( array $args ): bool => ( $args['show_in_rest'] ?? null ) === true
							&& ( $args['single'] ?? null ) === true,
				),
			)
			->andReturnTrue();

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				'beta',
				Mockery::on(
					static fn ( array $args ): bool => ( $args['show_in_rest'] ?? null ) === true
							&& ( $args['public'] ?? null ) === true
							&& ( $args['rewrite']['slug'] ?? null ) === 'beta',
				),
			)
			->andReturn( new stdClass() );

		Functions\expect( 'register_post_type' )
			->once()
			->with(
				'gamma',
				Mockery::on(
					static fn ( array $args ): bool => ( $args['show_in_rest'] ?? null ) === true
							&& ( $args['public'] ?? null ) === true
							&& ( $args['rewrite']['slug'] ?? null ) === 'gamma',
				),
			)
			->andReturn( new stdClass() );

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				'gamma',
				'gamma_is_open',
				Mockery::type( 'array' ),
			)
			->andReturnTrue();

		Functions\expect( 'register_post_meta' )
			->once()
			->with(
				'gamma',
				'gamma_amount',
				Mockery::type( 'array' ),
			)
			->andReturnTrue();

		$boot_unit->boot();

		( $this->init_callback )();
	}

	#[Test]
	public function register_post_types_logs_error_and_sets_failure_message_when_registration_fails(): void {

		$boot_unit = $this->create_boot_unit(
			new AlphaPostTypeConfig(),
			new BetaPostTypeConfig(),
		);

		Functions\expect( 'apply_filters' )
			->times( 4 )
			->andReturnUsing(
				// phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
				static fn ( string $hook_name, mixed $value ): mixed => $value,
			);

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'alpha', Mockery::type( 'array' ) )
			->andReturn( new stdClass() );

		Functions\expect( 'register_post_meta' )
			->once()
			->with( 'alpha', 'alpha_has_nested', Mockery::type( 'array' ) )
			->andReturnTrue();

		Functions\expect( 'register_post_type' )
			->once()
			->with( 'beta', Mockery::type( 'array' ) )
			->andReturn( new WP_Error( 'failed', 'Registrar failed' ) );

		// The dispatcher swallows the exception and calls this function.
		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with(
				Mockery::on(
					static fn ( string $message ): bool => str_contains(
						$message,
						'Cannot register post type "beta"',
					),
				),
			);

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Post type registration failed.',
				Mockery::on(
					static function ( array $context ): bool {

						if ( ( $context['service_class'] ?? null ) !== RegisterPostTypesBootUnit::class ) {
							return false;
						}

						if ( ( $context['component'] ?? null ) !== 'boot_units' ) {
							return false;
						}

						if ( ( $context['registered_count'] ?? null ) !== 1 ) {
							return false;
						}

						if ( ( $context['total_count'] ?? null ) !== 2 ) {
							return false;
						}

						$e = $context['exception'] ?? null;

						return $e instanceof PostTypeRegistrationException
							&& str_contains( $e->getMessage(), 'Cannot register post type "beta"' );
					},
				),
			);

		$boot_unit->boot();

		// No exception expected: InitActionHookDispatcher::handle() catches Throwable.
		( $this->init_callback )();
	}

	/**
	 * Creates the boot unit with the provided post type configs.
	 *
	 * @param PostTypeConfigInterface ...$post_type_configs The post type configs to register.
	 */
	private function create_boot_unit( PostTypeConfigInterface ...$post_type_configs ): RegisterPostTypesBootUnit {

		return new RegisterPostTypesBootUnit(
			$this->init_hook,
			$this->post_type_registrar,
			$this->logger,
			...$post_type_configs,
		);
	}
}
