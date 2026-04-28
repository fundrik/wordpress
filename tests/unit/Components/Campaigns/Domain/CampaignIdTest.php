<?php

declare(strict_types=1);

namespace Fundrik\WordPress\Tests\Components\Campaigns\Domain;

use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\WordPress\Components\Campaigns\Domain\CampaignId;
use Fundrik\WordPress\Components\Campaigns\Domain\Exceptions\InvalidCampaignIdException;
use Fundrik\WordPress\Tests\FundrikTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass( CampaignId::class )]
#[CoversClass( InvalidCampaignIdException::class )]
final class CampaignIdTest extends FundrikTestCase {

	#[Test]
	public function from_entity_id_returns_campaign_id_for_integer_entity_id(): void {

		$id = CampaignId::from_entity_id( EntityId::create( 42 ) );

		self::assertSame( 42, $id->get_value() );
	}

	#[Test]
	public function from_value_returns_campaign_id_for_positive_integer(): void {

		$id = CampaignId::from_value( 24 );

		self::assertSame( 24, $id->get_value() );
	}

	#[Test]
	public function to_entity_id_returns_wrapped_entity_id(): void {

		$id = CampaignId::from_value( 24 );

		self::assertEquals( EntityId::create( 24 ), $id->to_entity_id() );
	}

	#[Test]
	public function from_entity_id_throws_for_uuid_entity_id(): void {

		$this->expectException( InvalidCampaignIdException::class );
		$this->expectExceptionMessage(
			'Campaign ID must be a positive integer. Given: 123e4567-e89b-12d3-a456-426614174000.',
		);

		CampaignId::from_entity_id(
			EntityId::create( '123e4567-e89b-12d3-a456-426614174000' ),
		);
	}

	#[Test]
	public function from_value_throws_for_non_positive_integer(): void {

		$this->expectException( InvalidCampaignIdException::class );
		$this->expectExceptionMessage( 'Campaign ID must be a positive integer. Given: 0.' );

		CampaignId::from_value( 0 );
	}
}
