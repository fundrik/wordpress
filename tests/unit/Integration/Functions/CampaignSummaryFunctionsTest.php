<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Functions;

use Fundrik\WordPress\Integration\Services\CampaignSummaryDisplayService;
use Fundrik\WordPress\Kernel\Container\RuntimeContainer;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Illuminate\Contracts\Container\Container as LaravelContainerInterface;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

#[CoversFunction( 'fundrik_get_campaign_summary' )]
#[CoversFunction( 'fundrik_the_campaign_summary' )]
#[UsesClass( RuntimeContainer::class )]
final class CampaignSummaryFunctionsTest extends MockeryTestCase {

	private LaravelContainerInterface&MockInterface $container;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/CampaignSummaryFunctions.php';

		RuntimeContainer::reset();
		$this->container = Mockery::mock( LaravelContainerInterface::class );
	}

	#[Override]
	protected function tearDown(): void {

		RuntimeContainer::reset();

		parent::tearDown();
	}

	#[Test]
	public function fundrik_get_campaign_summary_throws_when_runtime_container_is_unavailable(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Fundrik runtime container is not available.' );

		fundrik_get_campaign_summary( 42 );
	}

	#[Test]
	public function fundrik_get_campaign_summary_does_not_catch_service_resolution_failure(): void {

		$exception = new RuntimeException( 'Campaign summary display service is unavailable.' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( CampaignSummaryDisplayService::class )
			->andThrow( $exception );

		RuntimeContainer::set( $this->container );

		$this->expectExceptionObject( $exception );

		fundrik_get_campaign_summary( 42 );
	}

	#[Test]
	public function fundrik_get_campaign_summary_returns_rendered_markup(): void {

		$options = [
			'showStatus' => false,
			'showGoal' => true,
		];
		$service = Mockery::mock();
		$service
			->shouldReceive( 'render' )
			->once()
			->with( 42, $options )
			->andReturn( '<div>Campaign summary</div>' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( CampaignSummaryDisplayService::class )
			->andReturn( $service );

		RuntimeContainer::set( $this->container );

		self::assertSame(
			'<div>Campaign summary</div>',
			fundrik_get_campaign_summary( 42, $options ),
		);
	}

	#[Test]
	public function fundrik_the_campaign_summary_echoes_rendered_markup_with_default_arguments(): void {

		$service = Mockery::mock();
		$service
			->shouldReceive( 'render' )
			->once()
			->with( null, [] )
			->andReturn( '<div>Campaign summary</div>' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( CampaignSummaryDisplayService::class )
			->andReturn( $service );

		RuntimeContainer::set( $this->container );

		$this->expectOutputString( '<div>Campaign summary</div>' );

		fundrik_the_campaign_summary();
	}
}
