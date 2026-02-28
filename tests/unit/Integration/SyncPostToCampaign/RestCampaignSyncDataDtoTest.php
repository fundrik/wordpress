<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Integration\SyncPostToCampaign;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\WordPress\Integration\SyncPostToCampaign\RestCampaignSyncDataDto;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( RestCampaignSyncDataDto::class )]
final class RestCampaignSyncDataDtoTest extends FundrikTestCase {

	#[Test]
	public function constructor_assigns_all_fields(): void {

		$id = EntityId::create( 10 );
		$version = EntityVersion::create( 3 );

		$dto = new RestCampaignSyncDataDto(
			id: $id,
			title: 'Title',
			version: $version,
			is_active: false,
			is_open: true,
			has_target: false,
			target_amount: 123,
		);

		self::assertSame( $id, $dto->id );
		self::assertSame( 'Title', $dto->title );
		self::assertSame( $version, $dto->version );
		self::assertFalse( $dto->is_active );
		self::assertTrue( $dto->is_open );
		self::assertFalse( $dto->has_target );
		self::assertSame( 123, $dto->target_amount );
	}
}
