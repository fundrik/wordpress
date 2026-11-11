<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\Functions;

use Fundrik\WordPress\Integration\Services\DonationFormDisplayService;
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

#[CoversFunction( 'fundrik_get_donation_form' )]
#[CoversFunction( 'fundrik_the_donation_form' )]
#[UsesClass( RuntimeContainer::class )]
final class DonationFormFunctionsTest extends MockeryTestCase {

	private LaravelContainerInterface&MockInterface $container;

	#[Override]
	protected function setUp(): void {

		parent::setUp();

		require_once dirname( __DIR__, 4 ) . '/src/php/Integration/Functions/DonationFormFunctions.php';

		RuntimeContainer::reset();
		$this->container = Mockery::mock( LaravelContainerInterface::class );
	}

	#[Override]
	protected function tearDown(): void {

		RuntimeContainer::reset();

		parent::tearDown();
	}

	#[Test]
	public function fundrik_get_donation_form_throws_when_runtime_container_is_unavailable(): void {

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( 'Fundrik runtime container is not available.' );

		fundrik_get_donation_form( 42 );
	}

	#[Test]
	public function fundrik_get_donation_form_does_not_catch_service_resolution_failure(): void {

		$exception = new RuntimeException( 'Donation form display service is unavailable.' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( DonationFormDisplayService::class )
			->andThrow( $exception );

		RuntimeContainer::set( $this->container );

		$this->expectExceptionObject( $exception );

		fundrik_get_donation_form( 42 );
	}

	#[Test]
	public function fundrik_get_donation_form_returns_rendered_markup_for_given_id(): void {

		$service = Mockery::mock();
		$service
			->shouldReceive( 'render' )
			->once()
			->with( 42 )
			->andReturn( '<form>Donation form</form>' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( DonationFormDisplayService::class )
			->andReturn( $service );

		RuntimeContainer::set( $this->container );

		self::assertSame( '<form>Donation form</form>', fundrik_get_donation_form( 42 ) );
	}

	#[Test]
	public function fundrik_get_donation_form_passes_null_when_id_is_omitted(): void {

		$service = Mockery::mock();
		$service
			->shouldReceive( 'render' )
			->once()
			->with( null )
			->andReturn( '' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( DonationFormDisplayService::class )
			->andReturn( $service );

		RuntimeContainer::set( $this->container );

		self::assertSame( '', fundrik_get_donation_form() );
	}

	#[Test]
	public function fundrik_the_donation_form_echoes_rendered_markup(): void {

		$service = Mockery::mock();
		$service
			->shouldReceive( 'render' )
			->once()
			->with( 42 )
			->andReturn( '<form>Donation form</form>' );

		$this->container
			->shouldReceive( 'make' )
			->once()
			->with( DonationFormDisplayService::class )
			->andReturn( $service );

		RuntimeContainer::set( $this->container );

		$this->expectOutputString( '<form>Donation form</form>' );

		fundrik_the_donation_form( 42 );
	}
}
