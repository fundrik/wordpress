<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Brain\Monkey\Functions;
use Closure;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently\CreateDonationIdempotentlyHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsFieldRenderer;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CampaignSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\CheckoutSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\DonationFormSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Groups\GeneralSettingsGroup;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultAcceptsDonationsSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Campaign\CampaignDefaultHasTargetSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutCancelUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\Checkout\CheckoutSuccessUrlSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountLabelSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\DonationForm\DonationFormDefaultAmountSetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\ActiveGatewaySetting;
use Fundrik\WordPress\Integration\AdminSettings\Settings\General\CurrencySetting;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\RegisterRestApiRoutesBootUnit;
use Fundrik\WordPress\Integration\Gateways\GatewayResolver;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestApiInitActionHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\RestApi\Routes\CreateDonationCheckoutRestRequestHandler;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
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
	private DonationCheckoutRestRoute $route;
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

		$this->route = new DonationCheckoutRestRoute(
			$this->create_request_handler(),
		);
		$this->rest_server = Mockery::mock( WP_REST_Server::class );

		$this->boot_unit = new RegisterRestApiRoutesBootUnit(
			$this->rest_api_init_hook,
			new BootUnitLogger( $this->psr_logger ),
			$this->route,
		);
	}

	#[Test]
	public function boot_registers_all_routes_on_rest_api_init(): void {

		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				DonationCheckoutRestRoute::get_route_namespace(),
				DonationCheckoutRestRoute::get_route_path(),
				Mockery::on( self::matches_route_args( ... ) ),
			)
			->andReturn( true );

		Functions\expect( 'fundrik_set_failure_message' )->never();

		$this->boot_unit->boot();

		( $this->rest_api_init_callback )( $this->rest_server );
	}

	#[Test]
	public function boot_logs_error_when_route_registration_returns_false(): void {

		Functions\expect( 'register_rest_route' )
			->once()
			->with(
				DonationCheckoutRestRoute::get_route_namespace(),
				DonationCheckoutRestRoute::get_route_path(),
				Mockery::on( self::matches_route_args( ... ) ),
			)
			->andReturn( false );

		Functions\expect( 'fundrik_set_failure_message' )
			->once()
			->with( 'Failed to register REST route "fundrik/v1/checkout".' );

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
							&& ( $context['exception'] ?? null )->getMessage() === 'Failed to register REST route "fundrik/v1/checkout".';
					},
				),
			);

		$this->boot_unit->boot();

		( $this->rest_api_init_callback )( $this->rest_server );
	}

	/**
	 * Returns whether the given route args match the expected registration shape.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $route_args Route args.
	 *
	 * @return bool True when the args match.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	private static function matches_route_args( array $route_args ): bool {

		$endpoint = $route_args[0] ?? null;

		if ( ! is_array( $endpoint ) ) {
			return false;
		}

		return ( $endpoint['methods'] ?? null ) === WP_REST_Server::CREATABLE
			&& is_callable( $endpoint['permission_callback'] ?? null )
			&& is_callable( $endpoint['callback'] ?? null )
			&& is_array( $endpoint['args'] ?? null );
	}

	/**
	 * Creates a real request handler for route registration tests.
	 *
	 * @since 1.0.0
	 *
	 * @return CreateDonationCheckoutRestRequestHandler Request handler.
	 */
	private function create_request_handler(): CreateDonationCheckoutRestRequestHandler {

		$handler_logger = new RestRouteHandlerLogger( $this->psr_logger );
		$handler_logger->set_rest_route_handler_class( CreateDonationCheckoutRestRequestHandler::class );

		return new CreateDonationCheckoutRestRequestHandler(
			$handler_logger,
			new CreateDonationIdempotentlyHandler(
				new CreateDonationHandler(
					Mockery::mock( CampaignRepositoryPort::class ),
					new DonationFactory(),
					Mockery::mock( DonationRepositoryPort::class ),
					Mockery::mock( ApplicationEventBusPort::class ),
				),
				new FindDonationByIdHandler(
					Mockery::mock( DonationRepositoryPort::class ),
				),
			),
			new AdminSettingsReader(
				new OptionReader(
					Mockery::mock( StoragePort::class ),
				),
				new GeneralSettingsGroup( new CurrencySetting( new AdminSettingsFieldRenderer() ) ),
				new CheckoutSettingsGroup(
					new ActiveGatewaySetting(
						new AdminSettingsFieldRenderer(),
						Mockery::mock( \Fundrik\WordPress\Integration\Gateways\GatewayInterface::class ),
					),
					new CheckoutSuccessUrlSetting( new AdminSettingsFieldRenderer() ),
					new CheckoutCancelUrlSetting( new AdminSettingsFieldRenderer() ),
				),
				new CampaignSettingsGroup(
					new CampaignDefaultAcceptsDonationsSetting( new AdminSettingsFieldRenderer() ),
					new CampaignDefaultHasTargetSetting( new AdminSettingsFieldRenderer() ),
				),
				new DonationFormSettingsGroup(
					new DonationFormDefaultAmountSetting( new AdminSettingsFieldRenderer() ),
					new DonationFormDefaultAmountLabelSetting( new AdminSettingsFieldRenderer() ),
				),
			),
			Mockery::mock( GatewayResolver::class ),
			Mockery::mock( CampaignLookupService::class ),
		);
	}
}
