<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Services;

use Brain\Monkey\Filters;
use DateTimeImmutable;
use DateTimeZone;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Infrastructure\Repositories\CampaignReadRepository\CampaignReadException;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntimeInterface;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use WP_Post;

#[CoversClass( CampaignLookupService::class )]
final class CampaignLookupServiceTest extends WordPressTestCase {

	private CampaignReadPort&MockInterface $campaign_read;

	private WordPressRuntimeInterface&MockInterface $wp_runtime;

	private LoggerInterface&MockInterface $logger;

	private CampaignLookupService $campaign_lookup;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
		$this->wp_runtime = Mockery::mock( WordPressRuntimeInterface::class );
		$this->logger = Mockery::mock( LoggerInterface::class );
		$this->campaign_lookup = new CampaignLookupService(
			new CampaignQueryService(
				new ReadCampaignByIdHandler( $this->campaign_read ),
			),
			$this->wp_runtime,
			$this->logger,
		);
	}

	#[Test]
	public function get_returns_campaign_by_id(): void {

		$campaign = $this->make_campaign( 42 );
		$this->logger->shouldNotReceive( 'warning' );
		$this->logger->shouldNotReceive( 'error' );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( $campaign, 42 )
			->andReturn( $campaign );
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( $campaign );

		self::assertSame( $campaign, $this->campaign_lookup->get( 42 ) );
	}

	#[Test]
	public function get_returns_the_filtered_campaign(): void {

		$campaign = $this->make_campaign( 42 );
		$filtered_campaign = $this->make_campaign( 77 );
		$this->logger->shouldNotReceive( 'warning' );
		$this->logger->shouldNotReceive( 'error' );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( $campaign, 42 )
			->andReturn( $filtered_campaign );
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( $campaign );

		self::assertSame( $filtered_campaign, $this->campaign_lookup->get( 42 ) );
	}

	#[Test]
	public function get_returns_current_campaign_when_id_is_omitted(): void {

		$campaign = $this->make_campaign( 42 );
		$this->logger->shouldNotReceive( 'warning' );
		$this->logger->shouldNotReceive( 'error' );
		Filters\expectApplied( 'fundrik_get_campaign' )
			->once()
			->with( $campaign, 42 )
			->andReturn( $campaign );
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andReturn( $campaign );

		$this->wp_runtime
			->shouldReceive( 'get_current_post' )
			->once()
			->andReturn( $this->make_post( 42, CampaignPostTypeConfig::ID ) );

		self::assertSame( $campaign, $this->campaign_lookup->get() );
	}

	#[Test]
	public function get_returns_null_when_current_campaign_context_is_missing(): void {

		$this->logger->shouldNotReceive( 'warning' );
		$this->logger->shouldNotReceive( 'error' );
		Filters\expectApplied( 'fundrik_get_campaign' )->never();
		$this->wp_runtime
			->shouldReceive( 'get_current_post' )
			->once()
			->andReturn( null );

		$this->campaign_read->shouldNotReceive( 'find_by_id' );

		self::assertNull( $this->campaign_lookup->get() );
	}

	#[Test]
	public function get_returns_null_and_logs_warning_when_campaign_id_is_invalid(): void {

		Filters\expectApplied( 'fundrik_get_campaign' )->never();
		$this->wp_runtime->shouldNotReceive( 'get_current_post' );
		$this->campaign_read->shouldNotReceive( 'find_by_id' );
		$this->logger->shouldNotReceive( 'error' );
		$this->logger
			->shouldReceive( 'warning' )
			->once()
			->with(
				'Campaign lookup skipped due to invalid campaign ID.',
				Mockery::on(
					static fn ( array $context ): bool => $context['service_class'] === CampaignLookupService::class
						&& $context['logger_class'] === CampaignLookupService::class
						&& $context['component'] === 'campaign_lookup'
						&& $context['layer'] === 'integration'
						&& $context['system'] === 'wordpress'
						&& $context['operation'] === 'validate_campaign_id'
						&& $context['outcome'] === 'invalid'
						&& $context['campaign_id'] === 0
						&& $context['source'] === 'argument'
						&& $context['exception'] instanceof InvalidCampaignIdException,
				),
			);

		self::assertNull( $this->campaign_lookup->get( 0 ) );
	}

	#[Test]
	public function get_returns_null_and_logs_error_when_campaign_lookup_fails(): void {

		Filters\expectApplied( 'fundrik_get_campaign' )->never();
		$this->campaign_read
			->shouldReceive( 'find_by_id' )
			->once()
			->with(
				Mockery::on(
					static fn ( EntityId $id ): bool => $id->get_value() === 42,
				),
			)
			->andThrow( new CampaignReadException( 'Failed to read campaign.' ) );
		$this->logger->shouldNotReceive( 'warning' );
		$this->logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Campaign lookup failed.',
				Mockery::on(
					static fn ( array $context ): bool => $context['service_class'] === CampaignLookupService::class
						&& $context['logger_class'] === CampaignLookupService::class
						&& $context['component'] === 'campaign_lookup'
						&& $context['layer'] === 'integration'
						&& $context['system'] === 'wordpress'
						&& $context['operation'] === 'read_campaign'
						&& $context['outcome'] === 'failed'
						&& $context['campaign_id'] === 42
						&& $context['exception'] instanceof ReadCampaignByIdException,
				),
			);

		self::assertNull( $this->campaign_lookup->get( 42 ) );
	}

	private function make_campaign( int $id ): Campaign {

		return new Campaign(
			id: $id,
			title: 'Campaign',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 1_000,
			collected_amount: 0,
			donations_count: 0,
			created_at: UtcDateTime::create(
				new DateTimeImmutable( '2026-03-21 10:00:00', new DateTimeZone( 'UTC' ) ),
			),
			updated_at: null,
		);
	}

	private function make_post( int $id, string $post_type ): WP_Post {

		$post = Mockery::mock( WP_Post::class );
		$post->ID = $id;
		$post->post_type = $post_type;

		return $post;
	}
}
