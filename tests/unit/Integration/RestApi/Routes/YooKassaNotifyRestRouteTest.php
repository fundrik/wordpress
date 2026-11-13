<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\RestApi\Routes;

use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\RestRouteHandlerLogger;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRequestHandler;
use Fundrik\WordPress\Integration\RestApi\Routes\YooKassaNotifyRestRoute;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Illuminate\Container\Container;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use WP_REST_Server;
use YooKassa\Client;

#[CoversClass( YooKassaNotifyRestRoute::class )]
#[UsesClass( YooKassaNotifyRestRequestHandler::class )]
#[UsesClass( RestRouteHandlerLogger::class )]
#[UsesClass( Logger::class )]
final class YooKassaNotifyRestRouteTest extends WordPressTestCase {

	private YooKassaNotifyRestRequestHandler $request_handler;
	private YooKassaNotifyRestRoute $route;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->request_handler = $this->make_request_handler();
		$this->route = new YooKassaNotifyRestRoute( $this->request_handler );
	}

	#[Test]
	public function get_route_namespace_returns_v1_namespace(): void {

		self::assertSame( RestRouteDefinitions::NAMESPACE_V1, YooKassaNotifyRestRoute::get_route_namespace() );
	}

	#[Test]
	public function get_route_path_returns_yookassa_notification_path(): void {

		self::assertSame( '/gateways/yookassa/notify', YooKassaNotifyRestRoute::get_route_path() );
	}

	#[Test]
	public function get_route_args_returns_yookassa_notification_route_registration(): void {

		self::assertSame(
			[
				[
					'methods' => WP_REST_Server::CREATABLE,
					'permission_callback' => '__return_true',
					'callback' => [ $this->request_handler, 'handle' ],
				],
			],
			$this->route->get_route_args(),
		);
	}

	private function make_request_handler(): YooKassaNotifyRestRequestHandler {

		$container = new Container();

		foreach (
			[
				DonationRepositoryPort::class,
				ApplicationEventBusPort::class,
				LoggerInterface::class,
				Client::class,
			] as $dependency
		) {
			$container->instance( $dependency, Mockery::mock( $dependency ) );
		}

		return $container->make( YooKassaNotifyRestRequestHandler::class );
	}
}
