<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Functions;

use Fundrik\WordPress\Integration\ReadModels\Campaign;
use Fundrik\WordPress\Integration\Services\CampaignLookupService;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;
use Fundrik\WordPress\Tests\WordPressTestCase;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

#[CoversFunction( 'fundrik_get_campaign' )]
#[UsesClass( Campaign::class )]
#[UsesClass( RuntimeContainer::class )]
final class CampaignFunctionsTest extends WordPressTestCase {

	private LaravelContainerInterface&MockInterface $container;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/CampaignFunctions.php';

		RuntimeContainer::reset();
		$this->container = Mockery::mock( LaravelContainerInterface::class );
	}

	#[Override]
	protected function tearDown(): void {

		RuntimeContainer::reset();

		parent::tearDown();
	}

	#[Test]
	public function fundrik_get_campaign_throws_when_runtime_container_is_unavailable(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Fundrik runtime container is not available.' );

		fundrik_get_campaign( 42 );
	}

	#[Test]
	public function fundrik_get_campaign_does_not_catch_service_resolution_failure(): void {

		$exception = new RuntimeException( 'Campaign lookup service is unavailable.' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( CampaignLookupService::class )
			->andThrow( $exception );

		RuntimeContainer::set( $this->container );

		$this->expectExceptionObject( $exception );

		fundrik_get_campaign( 42 );
	}

	#[Test]
	public function fundrik_get_campaign_returns_campaign_from_lookup_service_for_given_id(): void {

		$campaign = $this->make_campaign_read_model();
		$service = Mockery::mock();
		$service
			->shouldReceive( 'get' )
			->once()
			->with( 42 )
			->andReturn( $campaign );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( CampaignLookupService::class )
			->andReturn( $service );

		RuntimeContainer::set( $this->container );

		self::assertSame( $campaign, fundrik_get_campaign( 42 ) );
	}

	#[Test]
	public function fundrik_get_campaign_passes_null_when_id_is_omitted(): void {

		$service = Mockery::mock();
		$service
			->shouldReceive( 'get' )
			->once()
			->with( null )
			->andReturn( null );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( CampaignLookupService::class )
			->andReturn( $service );

		RuntimeContainer::set( $this->container );

		self::assertNull( fundrik_get_campaign() );
	}
}
