<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Services;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign as CoreCampaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Tests\Fixtures\WordPress\WpPost;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\LoggerInterface;

#[CoversClass( CampaignLookupService::class )]
#[UsesClass( CampaignId::class )]
#[UsesClass( InvalidCampaignIdException::class )]
#[UsesClass( CampaignReadException::class )]
#[UsesClass( Campaign::class )]
final class CampaignLookupServiceTest extends WordPressTestCase {

	private const int CAMPAIGN_ID = 42;
	private const string PERMALINK = 'https://example.test/campaigns/help-the-shelter/';
	private const int FEATURED_IMAGE_ID = 91;

	private CampaignReadPort&MockInterface $campaign_read;
	private WordPressRuntimeInterface&MockInterface $wp_runtime;
	private LoggerInterface&MockInterface $logger;
	private string|false $get_permalink_result;
	private int|false $get_post_thumbnail_id_result;
	private CampaignLookupService $service;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->wp_runtime = Mockery::mock( WordPressRuntimeInterface::class );
		$this->logger = Mockery::mock( LoggerInterface::class );
		$this->set_wordpress_campaign_data( permalink: self::PERMALINK, featured_image_id: self::FEATURED_IMAGE_ID );

		Functions\when( 'get_permalink' )->alias(
			fn ( int $campaign_id ): string|false => $campaign_id === self::CAMPAIGN_ID
				? $this->get_permalink_result
				: false,
		);
		Functions\when( 'get_post_thumbnail_id' )->alias(
			fn ( int $campaign_id ): int|false => $campaign_id === self::CAMPAIGN_ID
				? $this->get_post_thumbnail_id_result
				: false,
		);

		$this->service = new CampaignLookupService(
			new CampaignQueryService( new ReadCampaignByIdHandler( $this->campaign_read ) ),
			$this->wp_runtime,
			$this->logger,
		);
	}

	#[Test]
	public function get_returns_campaign_by_id(): void {

		$core_campaign = $this->make_core_campaign_read_model(
			updated_at: UtcDateTime::create_from_format( '2026-11-02 11:30:00', 'Y-m-d H:i:s' ),
		);
		$expected = $this->make_campaign_read_model(
			id: self::CAMPAIGN_ID,
			title: 'Help the shelter',
			permalink: self::PERMALINK,
			featured_image_id: self::FEATURED_IMAGE_ID,
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 100_000,
			collected_amount: 25_000,
			donations_count: 17,
			created_at: $core_campaign->get_created_at(),
			updated_at: $core_campaign->get_updated_at(),
		);

		$this->expect_campaign_read( self::CAMPAIGN_ID, $core_campaign );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( Mockery::type( Campaign::class ), self::CAMPAIGN_ID );

		$result = $this->service->get( self::CAMPAIGN_ID );

		self::assertEquals( $expected, $result );
	}

	#[Test]
	public function get_maps_unavailable_wordpress_data_to_null(): void {

		$this->set_wordpress_campaign_data( permalink: '', featured_image_id: 0 );
		$core_campaign = $this->make_core_campaign_read_model();
		$expected = $this->make_campaign_read_model(
			id: self::CAMPAIGN_ID,
			title: 'Help the shelter',
			permalink: null,
			featured_image_id: null,
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 100_000,
			collected_amount: 25_000,
			donations_count: 17,
			created_at: $core_campaign->get_created_at(),
			updated_at: $core_campaign->get_updated_at(),
		);

		$this->expect_campaign_read( self::CAMPAIGN_ID, $core_campaign );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( Mockery::type( Campaign::class ), self::CAMPAIGN_ID );

		$result = $this->service->get( self::CAMPAIGN_ID );

		self::assertEquals( $expected, $result );
	}

	#[Test]
	public function get_returns_filtered_campaign(): void {

		$core_campaign = $this->make_core_campaign_read_model();
		$filtered_campaign = $this->make_campaign_read_model( id: 77 );

		$this->expect_campaign_read( self::CAMPAIGN_ID, $core_campaign );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( Mockery::type( Campaign::class ), self::CAMPAIGN_ID )
			->andReturn( $filtered_campaign );

		$result = $this->service->get( self::CAMPAIGN_ID );

		self::assertSame( $filtered_campaign, $result );
	}

	#[Test]
	public function get_uses_original_campaign_when_filter_result_is_invalid(): void {

		$this->expect_campaign_read( self::CAMPAIGN_ID, $this->make_core_campaign_read_model() );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( Mockery::type( Campaign::class ), self::CAMPAIGN_ID )
			->andReturn( 'invalid' );

		Functions\expect( '_doing_it_wrong' )
			->once()
			->with( 'fundrik_get_campaign', 'Filter must return a campaign read model. Given: string.', '1.0.0' );

		$result = $this->service->get( self::CAMPAIGN_ID );

		self::assertInstanceOf( Campaign::class, $result );
		self::assertSame( self::CAMPAIGN_ID, $result->get_id() );
	}

	#[Test]
	public function get_returns_current_campaign_when_id_is_omitted(): void {

		$this->wp_runtime
			->shouldReceive( 'get_current_post' )
			->once()
			->andReturn( $this->make_post( self::CAMPAIGN_ID, CampaignPostTypeConfig::ID ) );

		$this->expect_campaign_read( self::CAMPAIGN_ID, $this->make_core_campaign_read_model() );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( Mockery::type( Campaign::class ), self::CAMPAIGN_ID );

		$result = $this->service->get();

		self::assertInstanceOf( Campaign::class, $result );
		self::assertSame( self::CAMPAIGN_ID, $result->get_id() );
	}

	#[Test]
	public function get_returns_null_when_current_post_is_missing(): void {

		$this->wp_runtime
			->shouldReceive( 'get_current_post' )
			->once()
			->andReturn( null );

		self::assertNull( $this->service->get() );
	}

	#[Test]
	public function get_returns_null_when_current_post_is_not_a_campaign(): void {

		$this->wp_runtime
			->shouldReceive( 'get_current_post' )
			->once()
			->andReturn( $this->make_post( self::CAMPAIGN_ID, 'post' ) );

		self::assertNull( $this->service->get() );
	}

	#[Test]
	#[DataProvider( 'invalid_campaign_id_cases' )]
	public function get_logs_warning_and_returns_null_for_invalid_campaign_id(
		?int $campaign_id,
		string $source,
	): void {

		if ( $campaign_id === null ) {
			$this->wp_runtime
				->shouldReceive( 'get_current_post' )
				->once()
				->andReturn( $this->make_post( 0, CampaignPostTypeConfig::ID ) );
		}

		$this->logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Campaign lookup skipped due to invalid campaign ID.',
				Mockery::on(
					static fn ( array $context ): bool => self::matches_invalid_id_log( $context, $source ),
				),
			);

		self::assertNull( $this->service->get( $campaign_id ) );
	}

	public static function invalid_campaign_id_cases(): iterable {

		yield 'argument' => [ 0, 'argument' ];
		yield 'current campaign post' => [ null, 'current_post' ];
	}

	#[Test]
	public function get_returns_null_when_campaign_is_not_found(): void {

		$this->expect_campaign_read( self::CAMPAIGN_ID, null );

		self::assertNull( $this->service->get( self::CAMPAIGN_ID ) );
	}

	#[Test]
	public function get_logs_error_and_returns_null_when_campaign_lookup_fails(): void {

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === self::CAMPAIGN_ID,
				),
			)
			->andThrow( new CampaignReadException( 'Database query failed.' ) );

		$this->logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign lookup failed.',
				Mockery::on( self::matches_lookup_failure_log( ... ) ),
			);

		self::assertNull( $this->service->get( self::CAMPAIGN_ID ) );
	}

	private function make_post( int $id, string $post_type ): WpPost {

		return new WpPost( post_type: $post_type, id: $id );
	}

	private function set_wordpress_campaign_data( string|false $permalink, int|false $featured_image_id, ): void {

		$this->get_permalink_result = $permalink;
		$this->get_post_thumbnail_id_result = $featured_image_id;
	}

	private function expect_campaign_read( int $campaign_id, ?CoreCampaign $campaign ): void {

		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === $campaign_id,
				),
			)
			->andReturn( $campaign );
	}

	private static function matches_invalid_id_log( array $context, string $source ): bool {

		$exception = $context['exception'] ?? null;
		unset( $context['exception'] );

		return $exception instanceof InvalidCampaignIdException
			&& $exception->getMessage() === 'Campaign ID must be a positive integer. Given: 0.'
			&& $context === [
				'service_class' => CampaignLookupService::class,
				'component' => 'campaign_lookup',
				'layer' => 'integration',
				'system' => 'wordpress',
				'operation' => 'validate_campaign_id',
				'outcome' => 'invalid',
				'campaign_id' => 0,
				'source' => $source,
			];
	}

	private static function matches_lookup_failure_log( array $context ): bool {

		$exception = $context['exception'] ?? null;
		unset( $context['exception'] );

		return $exception instanceof ReadCampaignByIdException
			&& $exception->getMessage() === 'Failed to retrieve campaign "42".'
			&& $exception->getPrevious() instanceof CampaignReadException
			&& $context === [
				'service_class' => CampaignLookupService::class,
				'component' => 'campaign_lookup',
				'layer' => 'integration',
				'system' => 'wordpress',
				'operation' => 'read_campaign',
				'outcome' => 'failed',
				'campaign_id' => self::CAMPAIGN_ID,
			];
	}
}
