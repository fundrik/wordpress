<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncDataDto;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestPreInsertCampaignSyncDataValidator;
use Fundrik\WordPress\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\WordPress\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use WP_Error;

#[CoversClass( RestPreInsertCampaignSyncDataValidator::class )]
#[UsesClass( RestCampaignSyncDataDto::class )]
final class RestPreInsertCampaignSyncDataValidatorTest extends MockeryTestCase {

	private CampaignFactory $campaign_factory;
	private CampaignRepositoryPort&MockInterface $campaign_repository;

	private RestPreInsertCampaignSyncDataValidator $validator;

	protected function setUp(): void {

		parent::setUp();

		$this->campaign_factory = new CampaignFactory();
		$this->campaign_repository = Mockery::mock( CampaignRepositoryPort::class );

		$this->validator = new RestPreInsertCampaignSyncDataValidator(
			$this->campaign_factory,
			$this->campaign_repository,
		);
	}

	#[Test]
	public function validate_or_error_rejects_when_domain_rejects_payload(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: '', // invalid -> CampaignFactoryException
			version: EntityVersion::create( 3 ),
			accepts_donations: true,
			has_target: false,
			target_amount: null,
			target_currency: 'RUB',
		);

		$this->campaign_repository->shouldNotReceive( 'find_by_id' );

		$error = $this->validator->validate_or_error( $data );

		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_validation_failed', $error->get_error_code() );
		self::assertSame( 422, $error->get_error_data()['status'] );
	}

	#[Test]
	public function validate_or_error_rejects_when_target_is_enabled_without_a_positive_amount(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Ok',
			version: EntityVersion::create( 3 ),
			accepts_donations: true,
			has_target: true,
			target_amount: null,
			target_currency: 'RUB',
		);

		$this->campaign_repository->shouldNotReceive( 'find_by_id' );

		$error = $this->validator->validate_or_error( $data );

		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_validation_failed', $error->get_error_code() );
		self::assertSame( 422, $error->get_error_data()['status'] );
		self::assertSame(
			'Target amount must be positive when targeting is enabled. Given: null.',
			$error->get_error_message(),
		);
	}

	#[Test]
	public function validate_or_error_rejects_when_repository_lookup_fails(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Ok',
			version: EntityVersion::create( 3 ),
			accepts_donations: true,
			has_target: false,
			target_amount: null,
			target_currency: 'RUB',
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->with( Mockery::type( EntityId::class ) )
			->andThrow( new FakeCampaignRepositoryException( 'DB error.' ) );

		$error = $this->validator->validate_or_error( $data );

		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_version_check_failed', $error->get_error_code() );
		self::assertSame( 500, $error->get_error_data()['status'] );
	}

	#[Test]
	public function validate_or_error_accepts_when_versions_match(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Ok',
			version: EntityVersion::create( 5 ),
			accepts_donations: true,
			has_target: false,
			target_amount: null,
			target_currency: 'RUB',
		);

		$persisted = $this->campaign_factory->create_from_primitives(
			id: 10,
			version: 5,
			title: 'Persisted',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: null,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $persisted );

		$error = $this->validator->validate_or_error( $data );

		self::assertNull( $error );
	}

	#[Test]
	public function validate_or_error_rejects_when_versions_do_not_match(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Ok',
			version: EntityVersion::create( 3 ),
			accepts_donations: true,
			has_target: false,
			target_amount: null,
			target_currency: 'RUB',
		);

		$persisted = $this->campaign_factory->create_from_primitives(
			id: 10,
			version: 5,
			title: 'Persisted',
			accepts_donations: true,
			currency_code: 'RUB',
			target_amount: null,
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( $persisted );

		$error = $this->validator->validate_or_error( $data );

		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_version_mismatch', $error->get_error_code() );

		$payload = $error->get_error_data();
		self::assertSame( 409, $payload['status'] );
		self::assertSame( 3, $payload['expected_version'] );
		self::assertSame( 5, $payload['current_version'] );
	}

	#[Test]
	public function validate_or_error_rejects_when_campaign_is_not_found_and_expected_is_not_initial(): void {

		$data = new RestCampaignSyncDataDto(
			id: EntityId::create( 10 ),
			title: 'Ok',
			version: EntityVersion::create( 2 ),
			accepts_donations: true,
			has_target: false,
			target_amount: null,
			target_currency: 'RUB',
		);

		$this->campaign_repository
			->shouldReceive( 'find_by_id' )
			->once()
			->andReturn( null );

		$error = $this->validator->validate_or_error( $data );

		self::assertInstanceOf( WP_Error::class, $error );
		self::assertSame( 'fundrik_campaign_version_mismatch', $error->get_error_code() );

		$payload = $error->get_error_data();
		self::assertSame( 409, $payload['status'] );
		self::assertSame( 2, $payload['expected_version'] );
		self::assertSame( 1, $payload['current_version'] );
	}
}

