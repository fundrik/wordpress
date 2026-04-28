<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncData;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( RestCampaignSyncData::class )]
final class RestCampaignSyncDataTest extends FundrikTestCase {

	#[Test]
	public function constructor_assigns_all_fields(): void {

		$id = CampaignId::from_value( 10 );
		$version = EntityVersion::create( 3 );

		$dto = new RestCampaignSyncData(
			id: $id,
			title: 'Title',
			version: $version,
			accepts_donations: true,
			has_target: false,
			target_amount: 123,
			target_currency: 'RUB',
		);

		self::assertSame( $id, $dto->id );
		self::assertSame( 'Title', $dto->title );
		self::assertSame( $version, $dto->version );
		self::assertTrue( $dto->accepts_donations );
		self::assertFalse( $dto->has_target );
		self::assertSame( 123, $dto->target_amount );
		self::assertSame( 'RUB', $dto->target_currency );
		self::assertEquals( EntityId::create( 10 ), $dto->id->to_entity_id() );
	}
}


