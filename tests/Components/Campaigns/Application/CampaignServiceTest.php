<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Application;

use Fundrik\Core\Components\Campaigns\Application\CampaignAssembler as CoreCampaignAssembler;
use Fundrik\Core\Components\Campaigns\Application\CampaignDtoFactory as CoreCampaignDtoFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignAssembler;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignDto;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignService;
use Fundrik\WordPress\Components\Campaigns\Application\CampaignServiceLogger;
use Fundrik\WordPress\Components\Campaigns\Application\Exceptions\CampaignAssemblerException;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\WordPress\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPortInterface;
use Fundrik\WordPress\Components\Campaigns\Domain\Campaign;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignSlug;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass( CampaignService::class )]
#[UsesClass( CampaignAssembler::class )]
#[UsesClass( CampaignDto::class )]
#[UsesClass( Campaign::class )]
#[UsesClass( CampaignSlug::class )]
final class CampaignServiceTest extends MockeryTestCase {

	private CampaignRepositoryPortInterface&MockInterface $repository;
	private CampaignServiceLogger&MockInterface $logger;
	private CampaignService $service;

	protected function setUp(): void {

		parent::setUp();

		$this->repository = Mockery::mock( CampaignRepositoryPortInterface::class );

		$this->logger = Mockery::mock( CampaignServiceLogger::class )->shouldIgnoreMissing();

		$this->service = new CampaignService(
			new CampaignAssembler(
				new CoreCampaignDtoFactory(),
				new CoreCampaignAssembler(),
			),
			$this->repository,
			$this->logger,
		);
	}

	#[Test]
	public function find_campaign_by_id_returns_campaign(): void {

		$campaign_id = EntityId::create( 1 );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $this->make_campaign_dto() );

		$result = $this->service->find_campaign_by_id( $campaign_id );

		$this->assertInstanceOf( Campaign::class, $result );
	}

	#[Test]
	public function find_campaign_by_id_returns_null_when_not_found(): void {

		$campaign_id = EntityId::create( 999 );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( null );

		$result = $this->service->find_campaign_by_id( $campaign_id );

		$this->assertNull( $result );
	}

	#[Test]
	public function find_campaign_by_id_propagates_repository_exception(): void {

		$e = new FakeCampaignRepositoryException();
		$campaign_id = EntityId::create( 123 );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		$this->logger
			->shouldReceive( 'log_find_by_id_failed_repository' )
			->once()
			->with(
				$this->identicalTo( $campaign_id->value ),
				$this->identicalTo( $e ),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->find_campaign_by_id( $campaign_id );
	}

	#[Test]
	public function find_campaign_by_id_propagates_assembler_exception_with_invalid_dto(): void {

		$campaign_id = EntityId::create( 1 );

		$this->repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andReturn( $this->make_invalid_campaign_dto() );

		$this->logger
			->shouldReceive( 'log_find_by_id_failed_assembler' )
			->once()
			->with(
				$this->identicalTo( $campaign_id->value ),
				Mockery::type( CampaignAssemblerException::class ),
			);

		$this->expectException( CampaignAssemblerException::class );

		$this->service->find_campaign_by_id( $campaign_id );
	}

	#[Test]
	public function find_all_campaigns_returns_list_of_campaigns(): void {

		$dto1 = $this->make_campaign_dto();
		$dto2 = $this->make_campaign_dto( id: 2 );

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( [ $dto1, $dto2 ] );

		$result = $this->service->find_all_campaigns();

		$this->assertCount( 2, $result );
		$this->assertInstanceOf( Campaign::class, $result[0] );
		$this->assertInstanceOf( Campaign::class, $result[1] );
	}

	#[Test]
	public function find_all_campaigns_returns_empty_array_when_no_campaigns_found(): void {

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( [] );

		$result = $this->service->find_all_campaigns();

		$this->assertIsArray( $result );
		$this->assertCount( 0, $result );
	}

	#[Test]
	public function find_all_campaigns_propagates_repository_exception(): void {

		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andThrow( $e );

		$this->logger
			->shouldReceive( 'log_find_all_failed_repository' )
			->once()
			->with(
				$this->identicalTo( $e ),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->find_all_campaigns();
	}

	#[Test]
	public function find_all_campaigns_propagates_assembler_exception_with_invalid_dto(): void {

		$this->repository
			->shouldReceive( 'find_all' )
			->once()
			->andReturn( [ $this->make_invalid_campaign_dto() ] );

		$this->logger
			->shouldReceive( 'log_find_all_failed_assembler' )
			->once()
			->with(
				Mockery::type( CampaignAssemblerException::class ),
			);

		$this->expectException( CampaignAssemblerException::class );

		$this->service->find_all_campaigns();
	}

	#[Test]
	public function save_campaign_inserts_when_campaign_does_not_exist(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andReturn( false );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Campaign::class ) );

		$this->logger
			->shouldReceive( 'log_save_succeeded' )
			->once()
			->with(
				$this->identicalTo( $campaign->get_id() ),
				$this->identicalTo( 'create' ),
			);

		$this->service->save_campaign( $campaign );

		$this->assertTrue( true );
	}

	#[Test]
	public function save_campaign_updates_when_campaign_exists(): void {

		$campaign = $this->make_campaign();

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andReturn( true );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( Mockery::type( Campaign::class ) );

		$this->logger
			->shouldReceive( 'log_save_succeeded' )
			->once()
			->with(
				$this->identicalTo( $campaign->get_id() ),
				$this->identicalTo( 'update' ),
			);

		$this->service->save_campaign( $campaign );

		$this->assertTrue( true );
	}

	#[Test]
	public function save_campaign_propagates_repository_exception_from_exists(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andThrow( $e );

		$this->logger
			->shouldReceive( 'log_save_failed_repository' )
			->once()
			->with(
				$this->identicalTo( $campaign->get_id() ),
				$this->identicalTo( $e ),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->save_campaign( $campaign );
	}

	#[Test]
	public function save_campaign_propagates_repository_exception_from_insert(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->andReturn( false );

		$this->repository
			->shouldReceive( 'insert' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andThrow( $e );

		$this->logger
			->shouldReceive( 'log_save_failed_repository' )
			->once()
			->with(
				$this->identicalTo( $campaign->get_id() ),
				$this->identicalTo( $e ),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->save_campaign( $campaign );
	}

	#[Test]
	public function save_campaign_propagates_repository_exception_from_update(): void {

		$campaign = $this->make_campaign();
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'exists' )
			->once()
			->andReturn( true );

		$this->repository
			->shouldReceive( 'update' )
			->once()
			->with( Mockery::type( Campaign::class ) )
			->andThrow( $e );

		$this->logger
			->shouldReceive( 'log_save_failed_repository' )
			->once()
			->with(
				$this->identicalTo( $campaign->get_id() ),
				$this->identicalTo( $e ),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->save_campaign( $campaign );
	}

	#[Test]
	public function delete_campaign_calls_repository_delete(): void {

		$campaign_id = EntityId::create( 42 );

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $campaign_id ) );

		$this->logger
			->shouldReceive( 'log_delete_succeeded' )
			->once()
			->with( $this->identicalTo( $campaign_id->value ) );

		$this->service->delete_campaign( $campaign_id );

		$this->assertTrue( true );
	}

	#[Test]
	public function delete_campaign_propagates_repository_exception(): void {

		$campaign_id = EntityId::create( 7 );
		$e = new FakeCampaignRepositoryException();

		$this->repository
			->shouldReceive( 'delete' )
			->once()
			->with( $this->identicalTo( $campaign_id ) )
			->andThrow( $e );

		$this->logger
			->shouldReceive( 'log_delete_failed_repository' )
			->once()
			->with(
				$this->identicalTo( $campaign_id->value ),
				$this->identicalTo( $e ),
			);

		$this->expectException( CampaignRepositoryExceptionInterface::class );

		$this->service->delete_campaign( $campaign_id );
	}
}
