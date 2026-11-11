<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Boot\Units;

use Fundrik\WordPress\Infrastructure\Logger;
use Fundrik\WordPress\Integration\Boot\BootUnitLogger;
use Fundrik\WordPress\Integration\Boot\Units\LogCreateDonationRestRequestFailuresBootUnit;
use Fundrik\WordPress\Integration\Boot\Units\RestValidationFailure;
use Fundrik\WordPress\Integration\HookDispatchers\Dispatchers\RestPostDispatchFilterHookDispatcher;
use Fundrik\WordPress\Integration\HookDispatchers\HookDispatcherLogger;
use Fundrik\WordPress\Integration\RestApi\RestRouteDefinitions;
use Fundrik\WordPress\Integration\RestApi\Routes\DonationCheckoutRestRoute;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestRequest;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestResponse;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpRestServer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( LogCreateDonationRestRequestFailuresBootUnit::class )]
#[UsesClass( Logger::class )]
#[UsesClass( BootUnitLogger::class )]
#[UsesClass( RestValidationFailure::class )]
#[UsesClass( RestPostDispatchFilterHookDispatcher::class )]
#[UsesClass( HookDispatcherLogger::class )]
#[UsesClass( RestRouteDefinitions::class )]
#[UsesClass( DonationCheckoutRestRoute::class )]
final class LogCreateDonationRestRequestFailuresBootUnitTest extends WordPressTestCase {

	private const string CHECKOUT_ROUTE = '/fundrik/v1/checkout';

	private LogCreateDonationRestRequestFailuresBootUnit $boot_unit;
	private RestPostDispatchFilterHookDispatcher $hook_dispatcher;
	private LoggerInterface&MockInterface $psr_logger;
	private WpRestServer $server;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->hook_dispatcher = new RestPostDispatchFilterHookDispatcher(
			new HookDispatcherLogger( $this->psr_logger ),
		);
		$this->boot_unit = new LogCreateDonationRestRequestFailuresBootUnit(
			$this->hook_dispatcher,
			new BootUnitLogger( $this->psr_logger ),
		);
		$this->server = new WpRestServer();
	}

	#[Test]
	#[DataProvider( 'validation_failure_cases' )]
	public function boot_logs_create_donation_validation_failure(
		array $response_data,
		string $error_code,
		?string $error_message,
		array $invalid_params,
	): void {

		$response = new WpRestResponse( $response_data, 400 );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Create-donation REST request validation failed.',
				[
					'service_class' => LogCreateDonationRestRequestFailuresBootUnit::class,
					'logger_class' => BootUnitLogger::class,
					'component' => 'boot_units',
					'layer' => 'integration',
					'system' => 'wordpress',
					'route' => self::CHECKOUT_ROUTE,
					'method' => 'POST',
					'status' => 400,
					'error_code' => $error_code,
					'error_message' => $error_message,
					'invalid_params' => $invalid_params,
				],
			);

		$this->boot_unit->boot();

		self::assertSame(
			$response,
			$this->hook_dispatcher->dispatch(
				$response,
				$this->server,
				new WpRestRequest( 'POST', self::CHECKOUT_ROUTE ),
			),
		);
	}

	#[Test]
	public function boot_ignores_non_validation_create_donation_bad_request(): void {

		$response = new WpRestResponse(
			[
				'code' => 'fundrik_invalid_donation_request',
				'message' => 'Donation request is invalid.',
			],
			400,
		);

		$this->boot_unit->boot();

		self::assertSame(
			$response,
			$this->hook_dispatcher->dispatch(
				$response,
				$this->server,
				new WpRestRequest( 'POST', self::CHECKOUT_ROUTE ),
			),
		);
	}

	#[Test]
	#[DataProvider( 'malformed_response_data_cases' )]
	public function boot_logs_create_donation_bad_request_with_malformed_response_data(
		mixed $response_data,
		array $expected_context,
	): void {

		$response = new WpRestResponse( $response_data, 400 );

		$this->psr_logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Create-donation REST failure response data could not be extracted.',
				[
					'service_class' => LogCreateDonationRestRequestFailuresBootUnit::class,
					'logger_class' => BootUnitLogger::class,
					'component' => 'boot_units',
					'layer' => 'integration',
					'system' => 'wordpress',
					'route' => self::CHECKOUT_ROUTE,
					'method' => 'POST',
					'status' => 400,
					...$expected_context,
				],
			);

		$this->boot_unit->boot();

		self::assertSame(
			$response,
			$this->hook_dispatcher->dispatch(
				$response,
				$this->server,
				new WpRestRequest( 'POST', self::CHECKOUT_ROUTE ),
			),
		);
	}

	#[Test]
	#[DataProvider( 'unrelated_response_cases' )]
	public function boot_ignores_unrelated_response( string $method, string $route, int $status, ): void {

		$response = new WpRestResponse( [], $status );

		$this->boot_unit->boot();

		self::assertSame(
			$response,
			$this->hook_dispatcher->dispatch(
				$response,
				$this->server,
				new WpRestRequest( $method, $route ),
			),
		);
	}

	/**
	 * Returns REST validation failure cases.
	 *
	 * @return iterable<string, array{array<mixed>, string, string|null, list<string>}> Validation failure cases.
	 */
	public static function validation_failure_cases(): iterable {

		yield 'invalid parameters' => [
			[
				'code' => 'rest_invalid_param',
				'message' => 'Invalid parameters.',
				'data' => [
					'params' => [
						'amount' => 'Amount is invalid.',
						0 => 'Ignored numeric key.',
						'currency' => 'Currency is invalid.',
					],
				],
			],
			'rest_invalid_param',
			'Invalid parameters.',
			[ 'amount', 'currency' ],
		];

		yield 'missing parameters without optional details' => [
			[
				'code' => 'rest_missing_callback_param',
			],
			'rest_missing_callback_param',
			null,
			[],
		];

		yield 'malformed optional details' => [
			[
				'code' => 'rest_invalid_param',
				'message' => 42,
				'data' => [ 'params' => 'invalid' ],
			],
			'rest_invalid_param',
			null,
			[],
		];
	}

	/**
	 * Returns malformed response data cases.
	 *
	 * @return iterable<string, array{mixed, array<string, mixed>}> Malformed response data cases.
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 */
	public static function malformed_response_data_cases(): iterable {

		yield 'non-array data' => [
			'malformed',
			[ 'response_data_type' => 'string' ],
		];

		yield 'missing error code' => [
			[
				'message' => 'Bad request.',
				0 => 'Ignored numeric key.',
				'data' => [],
			],
			[
				'response_data_type' => 'array',
				'response_data_keys' => [ 'message', 'data' ],
			],
		];
	}

	/**
	 * Returns responses unrelated to create-donation validation failures.
	 *
	 * @return iterable<string, array{string, string, int}> Unrelated response cases.
	 */
	public static function unrelated_response_cases(): iterable {

		yield 'different method' => [ 'GET', self::CHECKOUT_ROUTE, 400 ];
		yield 'different route' => [ 'POST', '/fundrik/v1/other', 400 ];
		yield 'different status' => [ 'POST', self::CHECKOUT_ROUTE, 500 ];
	}
}
