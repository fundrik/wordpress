<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Functions;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use Fundrik\WordPress\Integration\PostTypes\Configs\CampaignPostTypeConfig;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Integration\WordPressRuntime\WordPressRuntime;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Illuminate\Container\Container;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use WP_Post;

#[CoversFunction( 'fundrik_get_campaign' )]
final class CampaignFunctionsTest extends WordPressTestCase {

	private CampaignReadPort&MockInterface $campaign_read;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/CampaignFunctions.php';

		RuntimeContainer::reset();
		$this->campaign_read = Mockery::mock( CampaignReadPort::class );
	}

	#[Override]
	protected function tearDown(): void {

		RuntimeContainer::reset();

		parent::tearDown();
	}

	#[Test]
	public function fundrik_get_campaign_throws_when_runtime_container_is_unavailable(): void {

		$this->expectException( LogicException::class );

		fundrik_get_campaign( 42 );
	}

	#[Test]
	public function fundrik_get_campaign_returns_campaign_from_the_runtime_container(): void {

		$campaign = $this->make_campaign( 42 );
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

		$container = new Container();
		$container->instance( CampaignLookupService::class, $this->create_campaign_lookup_service() );

		RuntimeContainer::set( $container );

		self::assertSame( $campaign, fundrik_get_campaign( 42 ) );
	}

	#[Test]
	public function fundrik_get_campaign_returns_the_filtered_campaign(): void {

		$campaign = $this->make_campaign( 42 );
		$filtered_campaign = $this->make_campaign( 77 );
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

		$container = new Container();
		$container->instance( CampaignLookupService::class, $this->create_campaign_lookup_service() );

		RuntimeContainer::set( $container );

		self::assertSame( $filtered_campaign, fundrik_get_campaign( 42 ) );
	}

	#[Test]
	public function fundrik_get_campaign_returns_current_campaign_when_id_is_omitted(): void {

		$campaign = $this->make_campaign( 42 );
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

		Functions\expect( 'get_post' )
			->once()
			->andReturn( $this->make_post( 42, CampaignPostTypeConfig::ID ) );

		$container = new Container();
		$container->instance( CampaignLookupService::class, $this->create_campaign_lookup_service() );

		RuntimeContainer::set( $container );

		self::assertSame( $campaign, fundrik_get_campaign() );
	}

	#[Test]
	public function fundrik_get_campaign_returns_null_when_current_campaign_context_is_missing(): void {

		Functions\expect( 'get_post' )->once()->andReturn( null );
		Filters\expectApplied( 'fundrik_get_campaign' )->never();

		$this->campaign_read->shouldNotReceive( 'find_by_id' );

		$container = new Container();
		$container->instance( CampaignLookupService::class, $this->create_campaign_lookup_service() );

		RuntimeContainer::set( $container );

		self::assertNull( fundrik_get_campaign() );
	}

	private function create_campaign_lookup_service(): CampaignLookupService {

		return new CampaignLookupService(
			new CampaignQueryService(
				new ReadCampaignByIdHandler(
					$this->campaign_read,
				),
			),
			new WordPressRuntime(),
			new NullLogger(),
		);
	}

	private function make_campaign( int $id ): Campaign {

		return new Campaign(
			id: $id,
			title: 'Campaign',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: 1_000,
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
