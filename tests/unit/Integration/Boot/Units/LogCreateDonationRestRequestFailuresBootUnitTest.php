<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Closure;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\LogCreateDonationRestRequestFailuresBootUnit;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPostDispatchFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Tests\Integration\HookDispatchers\DispatcherTestHelpers;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

#[CoversClass( LogCreateDonationRestRequestFailuresBootUnit::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( RestPostDispatchFilterHookDispatcher::class )]
final class LogCreateDonationRestRequestFailuresBootUnitTest extends WordPressTestCase {

	use DispatcherTestHelpers;

	private const string HOOK_NAME = 'rest_post_dispatch';

	private LogCreateDonationRestRequestFailuresBootUnit $boot_unit;
	private Closure $filter_callback;
	private LoggerInterface&MockInterface $boot_logger;
	private WP_REST_Server&MockInterface $server;

	protected function setUp(): void {

		parent::setUp();

		$dispatcher_logger = Mockery::mock( LoggerInterface::class );
		$dispatcher_logger->shouldIgnoreMissing();

		$rest_post_dispatch_hook = new RestPostDispatchFilterHookDispatcher(
			new HookDispatcherLogger( $dispatcher_logger ),
		);

		$this->filter_callback = $this->register_and_capture_filter_callback(
			self::HOOK_NAME,
			$rest_post_dispatch_hook->register( ... ),
		);

		$this->boot_logger = Mockery::mock( LoggerInterface::class );
		$this->server = Mockery::mock( WP_REST_Server::class );

		$this->boot_unit = new LogCreateDonationRestRequestFailuresBootUnit(
			$rest_post_dispatch_hook,
			new BootUnitLogger( $this->boot_logger ),
		);
	}

	#[Test]
	public function boot_logs_invalid_donation_request_validation_failures(): void {

		$this->expect_failure_message_never();

		$this->boot_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Create-donation REST request validation failed.',
				Mockery::on(
					static function ( array $context ): bool {

						return ( $context['service_class'] ?? null ) === LogCreateDonationRestRequestFailuresBootUnit::class
							&& ( $context['component'] ?? null ) === 'boot_units'
							&& ( $context['layer'] ?? null ) === 'integration'
							&& ( $context['system'] ?? null ) === 'wordpress'
							&& ( $context['route'] ?? null ) === '/fundrik/v1/donations'
							&& ( $context['method'] ?? null ) === 'POST'
							&& ( $context['status'] ?? null ) === 400
							&& ( $context['error_code'] ?? null ) === 'rest_invalid_param'
							&& ( $context['error_message'] ?? null ) === 'Invalid parameter(s): amount'
							&& ( $context['invalid_params'] ?? null ) === [ 'amount' ];
					},
				),
			);

		$this->boot_unit->boot();

		$request = new WP_REST_Request( 'POST', '/fundrik/v1/donations' );
		$response = new WP_REST_Response(
			[
				'code' => 'rest_invalid_param',
				'message' => 'Invalid parameter(s): amount',
				'data' => [
					'status' => 400,
					'params' => [
						'amount' => 'Amount must be at least 1.',
					],
				],
			],
			400,
		);

		$returned = ( $this->filter_callback )( $response, $this->server, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function boot_does_not_log_non_validation_create_donation_bad_requests(): void {

		$this->expect_failure_message_never();

		$this->boot_logger->shouldNotReceive( 'warning' );

		$this->boot_unit->boot();

		$request = new WP_REST_Request( 'POST', '/fundrik/v1/donations' );
		$response = new WP_REST_Response(
			[
				'code' => 'fundrik_invalid_donation_request',
				'message' => 'Cannot create donation "abc": request is invalid.',
				'data' => [
					'status' => 400,
				],
			],
			400,
		);

		$returned = ( $this->filter_callback )( $response, $this->server, $request );

		self::assertSame( $response, $returned );
	}

	#[Test]
	public function boot_logs_malformed_create_donation_bad_requests(): void {

		$this->expect_failure_message_never();

		$this->boot_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Create-donation REST failure response data could not be extracted.',
				Mockery::on(
					static function ( array $context ): bool {

						return ( $context['service_class'] ?? null ) === LogCreateDonationRestRequestFailuresBootUnit::class
							&& ( $context['component'] ?? null ) === 'boot_units'
							&& ( $context['layer'] ?? null ) === 'integration'
							&& ( $context['system'] ?? null ) === 'wordpress'
							&& ( $context['route'] ?? null ) === '/fundrik/v1/donations'
							&& ( $context['method'] ?? null ) === 'POST'
							&& ( $context['status'] ?? null ) === 400
							&& ( $context['response_data_type'] ?? null ) === 'array'
							&& ( $context['response_data_keys'] ?? null ) === [ 'message', 'data' ];
					},
				),
			);

		$this->boot_unit->boot();

		$request = new WP_REST_Request( 'POST', '/fundrik/v1/donations' );
		$response = new WP_REST_Response(
			[
				'message' => 'Bad request.',
				'data' => [
					'status' => 400,
				],
			],
			400,
		);

		$returned = ( $this->filter_callback )( $response, $this->server, $request );

		self::assertSame( $response, $returned );
	}
}
