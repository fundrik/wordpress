<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi\Routes;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\Gateway\DonationGatewayPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Infrastructure\Ports\Storage\StoragePort;
use Fundrik\WordPress\Integration\AdminSettings\AdminSettingsReader;
use Fundrik\WordPress\Integration\Helpers\OptionReader;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\RestApi\Routes\CreateDonationCheckoutRestRequestHandler;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Illuminate\Container\Container;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use WP_REST_Server;

#[CoversClass( DonationCheckoutRestRoute::class )]
#[UsesClass( CreateDonationCheckoutRestRequestHandler::class )]
#[UsesClass( RestRouteHandlerLogger::class )]
#[UsesClass( Logger::class )]
#[UsesClass( AdminSettingsReader::class )]
#[UsesClass( OptionReader::class )]
#[UsesClass( CampaignLookupService::class )]
final class DonationCheckoutRestRouteTest extends WordPressTestCase {

	private CreateDonationCheckoutRestRequestHandler $request_handler;
	private DonationCheckoutRestRoute $route;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->request_handler = $this->make_request_handler();
		$this->route = new DonationCheckoutRestRoute( $this->request_handler );
	}

	#[Test]
	public function get_route_namespace_returns_v1_namespace(): void {

		self::assertSame( RestRouteDefinitions::NAMESPACE_V1, DonationCheckoutRestRoute::get_route_namespace() );
	}

	#[Test]
	public function get_route_path_returns_checkout_path(): void {

		self::assertSame( '/checkout', DonationCheckoutRestRoute::get_route_path() );
	}

	#[Test]
	public function get_route_args_returns_checkout_route_registration(): void {

		self::assertSame(
			[
				[
					'methods' => WP_REST_Server::CREATABLE,
					'permission_callback' => '__return_true',
					'callback' => [ $this->request_handler, 'handle' ],
					'args' => [
						'donation_id' => [
							'required' => true,
							'type' => 'string',
							'format' => 'uuid',
						],
						'campaign_id' => [
							'required' => true,
							'type' => 'integer',
							'minimum' => 1,
						],
						'amount' => [
							'required' => true,
							'type' => 'integer',
							'minimum' => 1,
						],
					],
				],
			],
			$this->route->get_route_args(),
		);
	}

	private function make_request_handler(): CreateDonationCheckoutRestRequestHandler {

		$container = new Container();

		foreach (
			[
				CampaignReadPort::class,
				CampaignRepositoryPort::class,
				DonationRepositoryPort::class,
				DonationGatewayPort::class,
				ApplicationEventBusPort::class,
				StoragePort::class,
				WordPressRuntimeInterface::class,
				LoggerInterface::class,
			] as $dependency
		) {
			$container->instance( $dependency, Mockery::mock( $dependency ) );
		}

		return $container->make( CreateDonationCheckoutRestRequestHandler::class );
	}
}
